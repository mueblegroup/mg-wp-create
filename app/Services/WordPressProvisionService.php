<?php

namespace App\Services;

use App\Models\Site;
use RuntimeException;

class WordPressProvisionService
{
    public function __construct(
        protected RemoteCommandService $remoteCommandService,
    ) {
    }

    /**
     * Install WordPress for a provisioned Hestia site.
     *
     * IMPORTANT:
     * - $databaseName must be the FULL Hestia database name.
     *   Example: u13abc_wpdb
     *
     * - $databaseUser must be the FULL Hestia database username.
     *   Example: u13abc_wpuser
     *
     * - $databasePassword must be the actual database password currently set in Hestia/MySQL.
     */
    public function install(
        Site $site,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $this->assertRequiredSiteFields($site);
        $this->assertDatabaseCredentials($databaseName, $databaseUser, $databasePassword);

        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $results = [];

        $results[] = $this->run(
            'mkdir -p ' . escapeshellarg($sitePath),
            'Failed to prepare remote site directory.'
        );

        $results[] = $this->run(
            'cd ' . escapeshellarg($sitePath) . ' && pwd',
            'Failed to access remote site directory.'
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Hestia default placeholder files
        |--------------------------------------------------------------------------
        |
        | Hestia creates index.html / index.htm by default. If these remain inside
        | public_html, the frontend may show "Site under construction" even though
        | WordPress is installed correctly because the web server may serve index.html
        | before index.php.
        */
        $results[] = $this->removeHestiaDefaultIndexFiles($sitePath);

        /*
        |--------------------------------------------------------------------------
        | Download WordPress core if missing
        |--------------------------------------------------------------------------
        */
        $wpLoadExists = $this->run(
            'test -f ' . escapeshellarg($sitePath . '/wp-load.php'),
            null,
            false
        );

        if (! $this->ok($wpLoadExists)) {
            $results[] = $this->run(
                'cd ' . escapeshellarg($sitePath)
                . ' && ' . $wpBinary . ' core download --force --allow-root',
                'Failed to download WordPress core remotely.'
            );
        } else {
            $results[] = $this->step('wordpress_core_exists', true, 'WordPress core already exists.');
        }

        /*
        |--------------------------------------------------------------------------
        | Check installation status
        |--------------------------------------------------------------------------
        |
        | WP-CLI core is-installed may fail with DB errors if a stale wp-config.php
        | exists. So we do not trust it blindly. If the check fails, we recreate
        | wp-config.php using the latest DB credentials and then HARD verify DB.
        */
        $isInstalled = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' core is-installed --allow-root',
            null,
            false
        );

        if (! $this->ok($isInstalled)) {
            $results[] = $this->recreateWpConfig(
                $sitePath,
                $databaseName,
                $databaseUser,
                $databasePassword
            );

            // IMPORTANT: this must throw on failure. Previously the code returned
            // a failed step but still continued to wp core install, causing the
            // repeated "Error establishing a database connection" failure.
            $results[] = $this->verifyDatabaseConnectionOrFail($sitePath, $databaseName, $databaseUser);

            $results[] = $this->run(
                'cd ' . escapeshellarg($sitePath)
                . ' && ' . $wpBinary . ' core install'
                . ' --url=' . escapeshellarg('https://' . $site->fqdn)
                . ' --title=' . escapeshellarg($site->name)
                . ' --admin_user=' . escapeshellarg($site->wordpress_admin_username)
                . ' --admin_password=' . escapeshellarg($this->adminPassword($site))
                . ' --admin_email=' . escapeshellarg($site->wordpress_admin_email)
                . ' --skip-email'
                . ' --locale=' . escapeshellarg(config('wordpress.default_locale', 'en_US'))
                . ' --allow-root',
                'Failed to install WordPress remotely.'
            );
        } else {
            $results[] = $this->step('wordpress_already_installed', true, 'WordPress is already installed.');
        }

        /*
        |--------------------------------------------------------------------------
        | Always sync admin credentials
        |--------------------------------------------------------------------------
        */
        $results[] = $this->syncAdminUser($site);

        /*
        |--------------------------------------------------------------------------
        | Install Laravel -> WordPress SSO
        |--------------------------------------------------------------------------
        |
        | This creates/updates the MU plugin and writes MG_SSO_SECRET into
        | wp-config.php so the Laravel dashboard can open wp-admin directly.
        */
        $results[] = $this->installSsoPlugin($site);

        /*
        |--------------------------------------------------------------------------
        | Basic site settings
        |--------------------------------------------------------------------------
        */
        $results[] = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' option update blog_public 0 --allow-root',
            null,
            false
        );

        $results[] = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' rewrite structure ' . escapeshellarg('/%postname%/') . ' --hard --allow-root',
            null,
            false
        );

        $results[] = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' rewrite flush --hard --allow-root',
            null,
            false
        );

        return [
            'success' => true,
            'message' => 'WordPress installation completed.',
            'site_path' => $sitePath,
            'database_name' => $databaseName,
            'database_user' => $databaseUser,
            'steps' => $results,
        ];
    }

    /**
     * Create or update the WordPress admin user.
     */
    public function createAdmin(Site $site): array
    {
        return $this->syncAdminUser($site);
    }

    protected function removeHestiaDefaultIndexFiles(string $sitePath): array
    {
        $result = $this->run(
            'rm -f '
            . escapeshellarg($sitePath . '/index.html') . ' '
            . escapeshellarg($sitePath . '/index.htm') . ' '
            . escapeshellarg($sitePath . '/default.html') . ' '
            . escapeshellarg($sitePath . '/default.htm'),
            'Failed to remove Hestia default placeholder files.',
            false
        );

        return $this->step(
            'hestia_default_index_removed',
            $this->ok($result),
            $this->ok($result)
                ? 'Hestia default placeholder files removed.'
                : 'Could not remove Hestia default placeholder files.',
            [
                'site_path' => $sitePath,
                'result' => $result,
            ]
        );
    }

    protected function syncAdminUser(Site $site): array
    {
        $this->assertRequiredSiteFields($site);

        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $username = (string) $site->wordpress_admin_username;
        $email = (string) $site->wordpress_admin_email;
        $password = $this->adminPassword($site);
        $role = (string) config('wordpress.default_admin_role', 'administrator');

        $isInstalled = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' core is-installed --allow-root',
            null,
            false
        );

        if (! $this->ok($isInstalled)) {
            return $this->step(
                'admin_sync_skipped',
                false,
                'WordPress is not installed yet, so admin user cannot be synced.',
                ['check' => $isInstalled]
            );
        }

        $checkUser = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' user get '
            . escapeshellarg($username)
            . ' --field=ID --allow-root',
            null,
            false
        );

        if (! $this->ok($checkUser)) {
            $createUser = $this->run(
                'cd ' . escapeshellarg($sitePath)
                . ' && ' . $wpBinary . ' user create '
                . escapeshellarg($username) . ' '
                . escapeshellarg($email)
                . ' --user_pass=' . escapeshellarg($password)
                . ' --role=' . escapeshellarg($role)
                . ' --allow-root',
                'Failed to create WordPress admin user remotely.'
            );

            return $this->step(
                'admin_user_created',
                true,
                'WordPress admin user created successfully.',
                [
                    'username' => $username,
                    'email' => $email,
                    'result' => $createUser,
                ]
            );
        }

        $updateUser = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' user update '
            . escapeshellarg($username)
            . ' --user_pass=' . escapeshellarg($password)
            . ' --user_email=' . escapeshellarg($email)
            . ' --role=' . escapeshellarg($role)
            . ' --allow-root',
            'Failed to update WordPress admin password remotely.'
        );

        return $this->step(
            'admin_user_updated',
            true,
            'WordPress admin user already existed. Password, email, and role were updated.',
            [
                'username' => $username,
                'email' => $email,
                'result' => $updateUser,
            ]
        );
    }

    /**
     * Install/update the SSO MU plugin and set MG_SSO_SECRET in wp-config.php.
     *
     * This allows Laravel to generate a signed temporary URL that logs the
     * customer directly into this WordPress site's /wp-admin/ without exposing
     * or copying the admin password.
     */
    protected function installSsoPlugin(Site $site): array
    {
        $this->assertRequiredSiteFields($site);

        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $isInstalled = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' core is-installed --allow-root',
            null,
            false
        );

        if (! $this->ok($isInstalled)) {
            return $this->step(
                'sso_install_skipped',
                false,
                'WordPress is not installed yet, so SSO plugin installation was skipped.',
                ['check' => $isInstalled]
            );
        }

        $ssoSecret = (string) ($site->wordpress_sso_secret ?? '');

        if ($ssoSecret === '') {
            $ssoSecret = bin2hex(random_bytes(32));

            $site->forceFill([
                'wordpress_sso_secret' => $ssoSecret,
            ])->save();
        }

        $pluginCode = <<<'PHP'
<?php
/**
 * Plugin Name: Mueble Group SSO Login
 * Description: Secure one-click WordPress admin login from Laravel SaaS dashboard.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_post_nopriv_mg_sso_login', 'mg_sso_login_handler');
add_action('admin_post_mg_sso_login', 'mg_sso_login_handler');

function mg_sso_login_handler(): void
{
    $secret = defined('MG_SSO_SECRET') ? MG_SSO_SECRET : '';

    if ($secret === '') {
        wp_die('SSO is not configured.', 403);
    }

    $site_id = sanitize_text_field($_GET['site_id'] ?? '');
    $username = sanitize_user($_GET['username'] ?? '');
    $email = sanitize_email($_GET['email'] ?? '');
    $expires = (int) ($_GET['expires'] ?? 0);
    $nonce = sanitize_text_field($_GET['nonce'] ?? '');
    $signature = sanitize_text_field($_GET['signature'] ?? '');

    if (! $site_id || ! $username || ! $email || ! $expires || ! $nonce || ! $signature) {
        wp_die('Invalid SSO request.', 403);
    }

    if (time() > $expires) {
        wp_die('SSO link expired.', 403);
    }

    $payload = implode('|', [
        $site_id,
        $username,
        $email,
        $expires,
        $nonce,
    ]);

    $expected = hash_hmac('sha256', $payload, $secret);

    if (! hash_equals($expected, $signature)) {
        wp_die('Invalid SSO signature.', 403);
    }

    $user = get_user_by('login', $username);

    if (! $user) {
        $user = get_user_by('email', $email);
    }

    if (! $user) {
        wp_die('WordPress user not found.', 403);
    }

    if (! user_can($user, 'administrator')) {
        wp_die('User is not an administrator.', 403);
    }

    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

    wp_safe_redirect(admin_url());
    exit;
}
PHP;

        $encodedPlugin = base64_encode($pluginCode);

        $steps = [];

        $steps[] = $this->run(
            'mkdir -p ' . escapeshellarg($sitePath . '/wp-content/mu-plugins'),
            'Failed to create WordPress MU plugins directory.'
        );

        $steps[] = $this->run(
            'printf %s ' . escapeshellarg($encodedPlugin)
            . ' | base64 -d > ' . escapeshellarg($sitePath . '/wp-content/mu-plugins/mg-sso-login.php'),
            'Failed to write WordPress SSO MU plugin.'
        );

        $steps[] = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' config set MG_SSO_SECRET '
            . escapeshellarg($ssoSecret)
            . ' --type=constant --allow-root',
            'Failed to write MG_SSO_SECRET to wp-config.php.'
        );

        return $this->step(
            'sso_plugin_installed',
            true,
            'WordPress SSO plugin installed and configured successfully.',
            [
                'site_path' => $sitePath,
                'plugin_path' => $sitePath . '/wp-content/mu-plugins/mg-sso-login.php',
                'steps' => $steps,
            ]
        );
    }

    /**
     * Create the WordPress database through Hestia CLI.
     *
     * Hestia prefixes database name and user with the Hestia username.
     * Example:
     * - hestia username = u13abc
     * - database suffix = wpdb
     * - db user suffix = wpuser
     *
     * Real credentials:
     * - DB name = u13abc_wpdb
     * - DB user = u13abc_wpuser
     *
     * Critical retry fix:
     * If the database already exists from a failed provisioning attempt, we reset
     * the database user's password to the current generated password. Otherwise
     * wp-config.php would use a fresh password while MySQL still has the old one.
     */
    public function createDatabase(
        string $hestiaUsername,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $this->assertDatabaseCredentials($databaseName, $databaseUser, $databasePassword);
        $this->assertHestiaIdentifier($hestiaUsername, 'hestiaUsername');
        $this->assertHestiaIdentifier($databaseName, 'databaseName');
        $this->assertHestiaIdentifier($databaseUser, 'databaseUser');

        $addCommand = '/usr/local/hestia/bin/v-add-database '
            . escapeshellarg($hestiaUsername) . ' '
            . escapeshellarg($databaseName) . ' '
            . escapeshellarg($databaseUser) . ' '
            . escapeshellarg($databasePassword) . ' '
            . escapeshellarg('mysql') . ' '
            . escapeshellarg('localhost') . ' '
            . escapeshellarg('utf8mb4');

        $addResult = $this->run(
            $addCommand,
            'Failed to create database remotely through Hestia CLI.',
            false
        );

        $output = strtolower((string) (($addResult['stderr'] ?? '') . ' ' . ($addResult['stdout'] ?? '') . ' ' . ($addResult['message'] ?? '')));
        $alreadyExists = str_contains($output, 'already exists') || str_contains($output, 'exists');

        $passwordResetResult = null;

        if (! $this->ok($addResult)) {
            if (! $alreadyExists) {
                throw new RuntimeException(
                    'Failed to create database remotely through Hestia CLI. Output: ' . trim($output)
                );
            }

            // Retry/idempotency fix: if DB exists, make its password match the
            // password Laravel will write into wp-config.php.
            $passwordResetResult = $this->resetExistingDatabasePassword(
                $hestiaUsername,
                $databaseName,
                $databaseUser,
                $databasePassword
            );
        }

        $fullDatabaseName = $hestiaUsername . '_' . $databaseName;
        $fullDatabaseUser = $hestiaUsername . '_' . $databaseUser;

        return [
            'success' => true,
            'message' => $this->ok($addResult)
                ? 'Database created successfully.'
                : 'Database already existed. Database user password was reset to current credentials.',
            'hestia_username' => $hestiaUsername,
            'database_name_suffix' => $databaseName,
            'database_user_suffix' => $databaseUser,
            'full_database_name' => $fullDatabaseName,
            'full_database_user' => $fullDatabaseUser,
            'database_password' => $databasePassword,
            'result' => $addResult,
            'password_reset_result' => $passwordResetResult,
        ];
    }

    protected function resetExistingDatabasePassword(
        string $hestiaUsername,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $command = '/usr/local/hestia/bin/v-change-database-password '
            . escapeshellarg($hestiaUsername) . ' '
            . escapeshellarg($databaseName) . ' '
            . escapeshellarg($databaseUser) . ' '
            . escapeshellarg($databasePassword);

        $result = $this->run(
            $command,
            'Database exists, but failed to reset database user password through Hestia CLI.',
            false
        );

        if (! $this->ok($result)) {
            $output = trim((string) (($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? '') . ' ' . ($result['message'] ?? '')));

            throw new RuntimeException(
                'Database already exists, but password reset failed. Delete the failed Hestia database/site and retry. Output: ' . $output
            );
        }

        return $result;
    }

    /**
     * Install and activate a theme ZIP.
     */
    public function installTheme(Site $site, string $zipPath): array
    {
        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();
        $remoteZipPath = $this->remoteThemePath($zipPath);

        $checkZip = $this->run(
            'test -f ' . escapeshellarg($remoteZipPath),
            'Theme ZIP file does not exist on remote server.',
            false
        );

        if (! $this->ok($checkZip)) {
            return $this->step(
                'theme_zip_missing',
                false,
                'Theme ZIP file does not exist on remote server.',
                [
                    'theme_zip' => $remoteZipPath,
                    'check' => $checkZip,
                ]
            );
        }

        $isInstalled = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' core is-installed --allow-root',
            null,
            false
        );

        if (! $this->ok($isInstalled)) {
            return $this->step(
                'theme_install_skipped',
                false,
                'WordPress is not installed yet. Theme installation skipped.',
                [
                    'theme_zip' => $remoteZipPath,
                ]
            );
        }

        $install = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' theme install '
            . escapeshellarg($remoteZipPath)
            . ' --activate --allow-root',
            'Failed to install and activate the theme remotely.'
        );

        return [
            'success' => true,
            'message' => 'Theme installed and activated.',
            'theme_zip' => $remoteZipPath,
            'check' => $checkZip,
            'install' => $install,
        ];
    }

    /**
     * Force recreate wp-config.php with current DB credentials.
     */
    protected function recreateWpConfig(
        string $sitePath,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $wpBinary = $this->wpBinary();

        $steps = [];

        $steps[] = $this->run(
            'rm -f ' . escapeshellarg($sitePath . '/wp-config.php'),
            'Failed to remove stale wp-config.php before recreating it.',
            false
        );

        $steps[] = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' config create'
            . ' --dbname=' . escapeshellarg($databaseName)
            . ' --dbuser=' . escapeshellarg($databaseUser)
            . ' --dbpass=' . escapeshellarg($databasePassword)
            . ' --dbhost=' . escapeshellarg('localhost')
            . ' --skip-check'
            . ' --force'
            . ' --allow-root',
            'Failed to create wp-config.php remotely.'
        );

        return $this->step(
            'wp_config_recreated',
            true,
            'wp-config.php recreated with current database credentials.',
            [
                'database_name' => $databaseName,
                'database_user' => $databaseUser,
                'steps' => $steps,
            ]
        );
    }

    /**
     * Verify WordPress can connect to the database before running core install.
     * This throws on failure so provisioning stops with the real root cause.
     */
    protected function verifyDatabaseConnectionOrFail(string $sitePath, string $databaseName, string $databaseUser): array
    {
        $wpBinary = $this->wpBinary();

        $check = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' db check --allow-root',
            null,
            false
        );

        if (! $this->ok($check)) {
            $output = trim((string) (($check['stderr'] ?? '') . ' ' . ($check['stdout'] ?? '') . ' ' . ($check['message'] ?? '')));

            throw new RuntimeException(
                'WordPress database connection failed before install. '
                . 'DB_NAME=' . $databaseName . ', DB_USER=' . $databaseUser . '. '
                . 'This usually means Hestia database credentials do not match wp-config.php, '
                . 'or the database exists from an old failed attempt with a different password. '
                . 'Output: ' . $output
            );
        }

        return $this->step(
            'database_connection_ok',
            true,
            'WordPress database connection verified.',
            [
                'check' => $check,
            ]
        );
    }

    public function sitePath(Site $site): string
    {
        $root = rtrim((string) config('wordpress.sites_root', '/home'), '/');

        if (filled($site->hestia_username)) {
            return "{$root}/{$site->hestia_username}/web/{$site->fqdn}/public_html";
        }

        return "{$root}/tenants/{$site->fqdn}/public_html";
    }

    protected function wpBinary(): string
    {
        return escapeshellcmd((string) config('wordpress.wp_cli', '/usr/local/bin/wp'));
    }

    protected function remoteThemePath(string $zipPath): string
    {
        return rtrim((string) config('wordpress.remote_theme_base_path', '/home/admin/theme-packages'), '/')
            . '/'
            . ltrim($zipPath, '/');
    }

    protected function adminPassword(Site $site): string
    {
        $password = (string) ($site->wp_admin_password ?? '');

        if ($password === '') {
            throw new RuntimeException('Site is missing wp_admin_password.');
        }

        return $password;
    }

    protected function assertRequiredSiteFields(Site $site): void
    {
        $required = [
            'fqdn' => $site->fqdn,
            'name' => $site->name,
            'wordpress_admin_username' => $site->wordpress_admin_username,
            'wordpress_admin_email' => $site->wordpress_admin_email,
            'wp_admin_password' => $site->wp_admin_password ?? null,
        ];

        $missing = [];

        foreach ($required as $field => $value) {
            if ((string) $value === '') {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new RuntimeException('Site is missing required WordPress fields: ' . implode(', ', $missing));
        }
    }

    protected function assertDatabaseCredentials(
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): void {
        $missing = [];

        if ($databaseName === '') {
            $missing[] = 'databaseName';
        }

        if ($databaseUser === '') {
            $missing[] = 'databaseUser';
        }

        if ($databasePassword === '') {
            $missing[] = 'databasePassword';
        }

        if (! empty($missing)) {
            throw new RuntimeException('Missing database credentials: ' . implode(', ', $missing));
        }
    }

    protected function assertHestiaIdentifier(string $value, string $field): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            throw new RuntimeException("Invalid {$field}. Only letters, numbers, and underscores are allowed for Hestia identifiers.");
        }
    }

    protected function run(string $command, ?string $errorMessage = null, bool $throw = true): array
    {
        $result = $this->remoteCommandService->run($command, $errorMessage, $throw);

        if (! is_array($result)) {
            return [
                'success' => false,
                'message' => 'Remote command service returned invalid result.',
                'raw' => $result,
            ];
        }

        return $result;
    }

    protected function ok(array $result): bool
    {
        return (bool) ($result['success'] ?? false);
    }

    protected function step(string $action, bool $success, string $message, array $context = []): array
    {
        return [
            'action' => $action,
            'success' => $success,
            'message' => $message,
            'context' => $context,
        ];
    }

    
}

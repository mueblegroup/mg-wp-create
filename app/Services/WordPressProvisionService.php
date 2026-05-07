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
     * Important:
     * - $databaseName must be the FULL Hestia database name.
     *   Example: u13abc_wpdb
     *
     * - $databaseUser must be the FULL Hestia database username.
     *   Example: u13abc_wpuser
     *
     * - $databasePassword must be the actual database password used when creating the DB.
     */
    public function install(
        Site $site,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $results = [];

        $this->assertRequiredSiteFields($site);
        $this->assertDatabaseCredentials($databaseName, $databaseUser, $databasePassword);

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
        | Check whether WordPress is already installed
        |--------------------------------------------------------------------------
        | If WordPress is not installed, we force-recreate wp-config.php.
        | This avoids stale DB credentials causing:
        | "Error establishing a database connection"
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

            $results[] = $this->verifyDatabaseConnection($sitePath);

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
        | This is critical. On retry, WordPress may already be installed and the
        | user may already exist. We still force update password/email/role so
        | Laravel dashboard credentials match the real WordPress login.
        */
        $results[] = $this->syncAdminUser($site);

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
     *
     * This method always makes sure the dashboard password and WordPress password match.
     */
    public function createAdmin(Site $site): array
    {
        return $this->syncAdminUser($site);
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
     * Create the WordPress database through Hestia CLI.
     *
     * Hestia automatically prefixes database name and user with the Hestia username.
     * So if:
     * - hestia username = u13abc
     * - database suffix = wpdb
     * - db user suffix = wpuser
     *
     * The real DB credentials become:
     * - database name = u13abc_wpdb
     * - database user = u13abc_wpuser
     */
    public function createDatabase(
        string $hestiaUsername,
        string $databaseName,
        string $databaseUser,
        string $databasePassword
    ): array {
        $this->assertDatabaseCredentials($databaseName, $databaseUser, $databasePassword);

        $command = '/usr/local/hestia/bin/v-add-database '
            . escapeshellarg($hestiaUsername) . ' '
            . escapeshellarg($databaseName) . ' '
            . escapeshellarg($databaseUser) . ' '
            . escapeshellarg($databasePassword) . ' '
            . escapeshellarg('mysql') . ' '
            . escapeshellarg('localhost') . ' '
            . escapeshellarg('utf8mb4');

        $result = $this->run(
            $command,
            'Failed to create database remotely through Hestia CLI.',
            false
        );

        $output = strtolower((string) (($result['stderr'] ?? '') . ' ' . ($result['stdout'] ?? '') . ' ' . ($result['message'] ?? '')));

        if (! $this->ok($result) && ! str_contains($output, 'already exists') && ! str_contains($output, 'exists')) {
            throw new RuntimeException('Failed to create database remotely through Hestia CLI.');
        }

        $fullDatabaseName = $hestiaUsername . '_' . $databaseName;
        $fullDatabaseUser = $hestiaUsername . '_' . $databaseUser;

        return [
            'success' => true,
            'message' => $this->ok($result)
                ? 'Database created successfully.'
                : 'Database already exists, continuing with generated credentials.',
            'hestia_username' => $hestiaUsername,
            'database_name_suffix' => $databaseName,
            'database_user_suffix' => $databaseUser,
            'full_database_name' => $fullDatabaseName,
            'full_database_user' => $fullDatabaseUser,
            'database_password' => $databasePassword,
            'result' => $result,
        ];
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
     *
     * This fixes stale wp-config.php from failed/retried provisioning attempts.
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
     */
    protected function verifyDatabaseConnection(string $sitePath): array
    {
        $wpBinary = $this->wpBinary();

        $check = $this->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' db check --allow-root',
            null,
            false
        );

        if (! $this->ok($check)) {
            return $this->step(
                'database_connection_failed',
                false,
                'WordPress could not connect to the database using wp-config.php.',
                [
                    'check' => $check,
                ]
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
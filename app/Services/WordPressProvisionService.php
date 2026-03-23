<?php

namespace App\Services;

use App\Exceptions\ProvisioningException;
use App\Models\Site;

class WordPressProvisionService
{
    public function __construct(
        protected RemoteCommandService $remoteCommandService,
    ) {
    }

    public function install(Site $site, string $databaseName, string $databaseUser, string $databasePassword): array
    {
        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $results = [];

        $results[] = $this->remoteCommandService->run(
            'mkdir -p ' . escapeshellarg($sitePath),
            'Failed to prepare remote site directory.'
        );

        $wpConfigExists = $this->remoteCommandService->run(
            'test -f ' . escapeshellarg($sitePath . '/wp-config.php'),
            null,
            false
        );

        $wpLoadExists = $this->remoteCommandService->run(
            'test -f ' . escapeshellarg($sitePath . '/wp-load.php'),
            null,
            false
        );

        /*
        |--------------------------------------------------------------------------
        | Download WordPress core only if not already present
        |--------------------------------------------------------------------------
        */
        if (! $wpLoadExists['success']) {
            $results[] = $this->remoteCommandService->run(
                'cd ' . escapeshellarg($sitePath) . ' && ' . $wpBinary . ' core download --force --allow-root',
                'Failed to download WordPress core remotely.'
            );
        } else {
            $results[] = [
                'success' => true,
                'message' => 'WordPress core download skipped because files already exist.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Create wp-config.php only if it does not exist
        |--------------------------------------------------------------------------
        */
        if (! $wpConfigExists['success']) {
            $results[] = $this->remoteCommandService->run(
                'cd ' . escapeshellarg($sitePath) . ' && ' . $wpBinary . ' config create'
                . ' --dbname=' . escapeshellarg($databaseName)
                . ' --dbuser=' . escapeshellarg($databaseUser)
                . ' --dbpass=' . escapeshellarg($databasePassword)
                . ' --dbhost=' . escapeshellarg('localhost')
                . ' --skip-check --allow-root',
                'Failed to create wp-config.php remotely.'
            );
        } else {
            $results[] = [
                'success' => true,
                'message' => 'wp-config.php creation skipped because it already exists.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Install WordPress only if not already installed
        |--------------------------------------------------------------------------
        */
        $isInstalled = $this->remoteCommandService->run(
            'cd ' . escapeshellarg($sitePath) . ' && ' . $wpBinary . ' core is-installed --allow-root',
            null,
            false
        );

        if (! $isInstalled['success']) {
            $results[] = $this->remoteCommandService->run(
                'cd ' . escapeshellarg($sitePath) . ' && ' . $wpBinary . ' core install'
                . ' --url=' . escapeshellarg('https://' . $site->fqdn)
                . ' --title=' . escapeshellarg($site->name)
                . ' --admin_user=' . escapeshellarg($site->wordpress_admin_username)
                . ' --admin_password=' . escapeshellarg($site->wordpress_admin_password_plain)
                . ' --admin_email=' . escapeshellarg($site->wordpress_admin_email)
                . ' --skip-email'
                . ' --locale=' . escapeshellarg(config('wordpress.default_locale', 'en_US'))
                . ' --allow-root',
                'Failed to install WordPress remotely.'
            );
        } else {
            $results[] = [
                'success' => true,
                'message' => 'WordPress installation skipped because it is already installed.',
            ];
        }

        return [
            'success' => true,
            'site_path' => $sitePath,
            'database_name' => $databaseName,
            'database_user' => $databaseUser,
            'steps' => $results,
        ];
    }

    public function installTheme(Site $site, string $zipPath): array
    {
        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();
        $remoteZipPath = $this->remoteThemePath($zipPath);

        $checkZip = $this->remoteCommandService->run(
            'test -f ' . escapeshellarg($remoteZipPath),
            'Theme ZIP file does not exist on remote server.'
        );

        $install = $this->remoteCommandService->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' theme install '
            . escapeshellarg($remoteZipPath)
            . ' --activate --allow-root',
            'Failed to install and activate the theme remotely.'
        );

        return [
            'success' => true,
            'theme_zip' => $remoteZipPath,
            'check' => $checkZip,
            'install' => $install,
        ];
    }

    public function createAdmin(Site $site): array
    {
        $sitePath = $this->sitePath($site);
        $wpBinary = $this->wpBinary();

        $username = $site->wordpress_admin_username;
        $email = $site->wordpress_admin_email;
        $password = $site->wordpress_admin_password_plain;
        $role = config('wordpress.default_admin_role', 'administrator');

        $check = $this->remoteCommandService->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' user get '
            . escapeshellarg($username)
            . ' --field=ID --allow-root',
            null,
            false
        );

        if ($check['success']) {
            return [
                'success' => true,
                'message' => 'WordPress admin user already exists.',
            ];
        }

        $create = $this->remoteCommandService->run(
            'cd ' . escapeshellarg($sitePath)
            . ' && ' . $wpBinary . ' user create '
            . escapeshellarg($username) . ' '
            . escapeshellarg($email)
            . ' --user_pass=' . escapeshellarg($password)
            . ' --role=' . escapeshellarg($role)
            . ' --allow-root',
            'Failed to create WordPress admin user remotely.'
        );

        return [
            'success' => true,
            'username' => $username,
            'result' => $create,
        ];
    }

    public function createDatabase(string $hestiaUsername, string $databaseName, string $databaseUser, string $databasePassword): array
    {
        $command = '/usr/local/hestia/bin/v-add-database '
            . escapeshellarg($hestiaUsername) . ' '
            . escapeshellarg($databaseName) . ' '
            . escapeshellarg($databaseUser) . ' '
            . escapeshellarg($databasePassword) . ' '
            . escapeshellarg('mysql') . ' '
            . escapeshellarg('localhost') . ' '
            . escapeshellarg('utf8mb4');

        $result = $this->remoteCommandService->run(
            $command,
            'Failed to create database remotely through Hestia CLI.'
        );

        /*
        |--------------------------------------------------------------------------
        | Hestia prefixes DB and DB user with the account username
        |--------------------------------------------------------------------------
        */
        $fullDatabaseName = $hestiaUsername . '_' . $databaseName;
        $fullDatabaseUser = $hestiaUsername . '_' . $databaseUser;

        return [
            'success' => true,
            'hestia_username' => $hestiaUsername,
            'database_name_suffix' => $databaseName,
            'database_user_suffix' => $databaseUser,
            'full_database_name' => $fullDatabaseName,
            'full_database_user' => $fullDatabaseUser,
            'result' => $result,
        ];
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
}
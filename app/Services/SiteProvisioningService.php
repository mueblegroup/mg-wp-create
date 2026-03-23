<?php

namespace App\Services;

use App\Exceptions\ProvisioningException;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteProvisioningService
{
    public function __construct(
        protected HestiaService $hestiaService,
        protected WordPressProvisionService $wordpressService,
    ) {
    }

    public function provision(Site $site): void
    {
        $site->loadMissing(['plan', 'theme', 'user', 'provisioningLogs']);

        if (! $site->plan) {
            throw new ProvisioningException('Site plan is missing.');
        }

        if (! $site->user) {
            throw new ProvisioningException('Site owner is missing.');
        }

        $this->log($site, 'provisioning_started', 'info', 'Provisioning process started.', [
            'site_id' => $site->id,
            'fqdn' => $site->fqdn,
            'theme' => $site->theme?->slug ?? 'none',
        ]);

        $credentials = $this->getOrCreateCredentials($site);

        $site->setTemporaryProvisioningData([
            'wordpress_admin_password_plain' => $credentials['wp_admin_password'],
        ]);

        DB::transaction(function () use ($site, $credentials) {
            $site->update([
                'status' => Site::STATUS_PROVISIONING,
                'provisioning_error' => null,
                'wordpress_admin_username' => $credentials['wp_admin_username'],
                'wordpress_admin_email' => $site->user->email,
            ]);
        });

        $this->log($site, 'credentials_prepared', 'info', 'Provisioning credentials prepared.', [
            'wordpress_admin_username' => $credentials['wp_admin_username'],
            'database_name_suffix' => $credentials['db_name'],
            'database_user_suffix' => $credentials['db_user'],
            'theme' => $site->theme?->slug ?? 'none',
        ]);

        $packageName = $this->hestiaService->resolvePlanPackage($site->plan->name);

        $this->ensureHestiaUser($site, $packageName, $credentials);
        $this->ensureHestiaDomain($site);
        $this->ensureSsl($site);

        $databaseInfo = $this->ensureDatabase($site, $credentials);

        $wpInstall = $this->wordpressService->install(
            $site,
            $databaseInfo['full_database_name'],
            $databaseInfo['full_database_user'],
            $credentials['db_password'],
        );

        $this->log($site, 'wordpress_installed', 'success', 'WordPress installed successfully.', $wpInstall);

        if ($site->theme) {
            $themeInstall = $this->wordpressService->installTheme($site, $site->theme->zip_path);

            $this->log($site, 'theme_installed', 'success', 'Theme installed and activated.', $themeInstall);
        } else {
            $this->log($site, 'theme_skipped', 'info', 'No custom theme selected. Using default WordPress theme.');
        }

        $adminCreate = $this->wordpressService->createAdmin($site);

        $this->log($site, 'wordpress_admin_created', 'success', 'WordPress admin ensured.', $adminCreate);

        $site->refresh();

        $site->update([
            'status' => Site::STATUS_ACTIVE,
            'wordpress_admin_url' => 'https://' . $site->fqdn . '/wp-admin',
            'provisioned_at' => now(),
            'provisioning_error' => null,
        ]);

        $this->log($site, 'provisioning_completed', 'success', 'Provisioning completed successfully.', [
            'wordpress_admin_url' => $site->wordpress_admin_url,
            'wordpress_admin_username' => $credentials['wp_admin_username'],
            'wordpress_admin_password_plain' => $credentials['wp_admin_password'],
            'theme' => $site->theme?->slug ?? 'none',
        ]);
    }

    protected function ensureHestiaUser(Site $site, string $packageName, array $credentials): void
    {
        if ($this->hasSuccessfulLog($site, 'hestia_user_created')) {
            $this->log($site, 'hestia_user_skipped', 'info', 'Hestia user creation skipped because it was already completed previously.', [
                'username' => $site->hestia_username,
            ]);

            return;
        }

        $payload = [
            'username' => $site->hestia_username,
            'email' => $site->user->email,
            'password' => $credentials['hestia_password'],
            'package' => $packageName,
            'first_name' => $site->user->name,
        ];

        $result = $this->hestiaService->createUser($payload);

        $this->log($site, 'hestia_user_created', 'success', 'Hestia user created.', [
            'username' => $site->hestia_username,
            'package' => $packageName,
            'result' => $result,
        ]);
    }

    protected function ensureHestiaDomain(Site $site): void
    {
        if ($this->hasSuccessfulLog($site, 'hestia_domain_created')) {
            $this->log($site, 'hestia_domain_skipped', 'info', 'Hestia domain creation skipped because it was already completed previously.', [
                'domain' => $site->fqdn,
            ]);

            return;
        }

        $payload = [
            'username' => $site->hestia_username,
            'domain' => $site->fqdn,
        ];

        $result = $this->hestiaService->createDomain($payload);

        $this->log($site, 'hestia_domain_created', 'success', 'Hestia domain created.', [
            'domain' => $site->fqdn,
            'result' => $result,
        ]);
    }

    protected function ensureSsl(Site $site): void
    {
        if (! $this->hasSuccessfulLog($site, 'ssl_enabled')) {
            $sslResult = $this->hestiaService->enableLetsEncryptDomain(
                $site->hestia_username,
                $site->fqdn,
                '',
                'no'
            );

            $this->log($site, 'ssl_enabled', 'success', 'SSL enabled for domain using Let’s Encrypt.', $sslResult);
        } else {
            $this->log($site, 'ssl_skipped', 'info', 'SSL enable step skipped because it was already completed previously.', [
                'domain' => $site->fqdn,
            ]);
        }

        if (! $this->hasSuccessfulLog($site, 'ssl_force_enabled')) {
            $sslForceResult = $this->hestiaService->enableSslForce(
                $site->hestia_username,
                $site->fqdn,
                'yes',
                'no'
            );

            $this->log($site, 'ssl_force_enabled', 'success', 'HTTPS redirect enabled for domain.', $sslForceResult);
        } else {
            $this->log($site, 'ssl_force_skipped', 'info', 'HTTPS redirect step skipped because it was already completed previously.', [
                'domain' => $site->fqdn,
            ]);
        }

        if (! $this->hasSuccessfulLog($site, 'hsts_enabled')) {
            $hstsResult = $this->hestiaService->enableSslHsts(
                $site->hestia_username,
                $site->fqdn,
                'yes',
                'no'
            );

            $this->log($site, 'hsts_enabled', 'success', 'HSTS enabled for domain.', $hstsResult);
        } else {
            $this->log($site, 'hsts_skipped', 'info', 'HSTS step skipped because it was already completed previously.', [
                'domain' => $site->fqdn,
            ]);
        }
    }

    protected function ensureDatabase(Site $site, array $credentials): array
    {
        $fallback = [
            'full_database_name' => $site->hestia_username . '_' . $credentials['db_name'],
            'full_database_user' => $site->hestia_username . '_' . $credentials['db_user'],
        ];

        if ($this->hasSuccessfulLog($site, 'database_created')) {
            $this->log($site, 'database_skipped', 'info', 'Database creation skipped because it was already completed previously.', [
                'database_name_suffix' => $credentials['db_name'],
                'database_user_suffix' => $credentials['db_user'],
                'full_database_name' => $fallback['full_database_name'],
                'full_database_user' => $fallback['full_database_user'],
            ]);

            return $fallback;
        }

        $dbCreate = $this->wordpressService->createDatabase(
            $site->hestia_username,
            $credentials['db_name'],
            $credentials['db_user'],
            $credentials['db_password'],
        );

        $databaseInfo = [
            'full_database_name' => $dbCreate['full_database_name'],
            'full_database_user' => $dbCreate['full_database_user'],
        ];

        $this->log($site, 'database_created', 'success', 'Database created on remote Hestia server.', $dbCreate);

        return $databaseInfo;
    }

    protected function getOrCreateCredentials(Site $site): array
    {
        if (
            filled($site->db_name) &&
            filled($site->db_user) &&
            filled($site->db_password) &&
            filled($site->hestia_password) &&
            filled($site->wordpress_admin_username) &&
            filled($site->wp_admin_password)
        ) {
            return [
                'hestia_password' => $site->hestia_password,
                'db_name' => $site->db_name,
                'db_user' => $site->db_user,
                'db_password' => $site->db_password,
                'wp_admin_username' => $site->wordpress_admin_username,
                'wp_admin_password' => $site->wp_admin_password,
            ];
        }

        $credentials = $this->prepareCredentials($site);

        $site->update([
            'db_name' => $credentials['db_name'],
            'db_user' => $credentials['db_user'],
            'db_password' => $credentials['db_password'],
            'hestia_password' => $credentials['hestia_password'],
            'wp_admin_password' => $credentials['wp_admin_password'],
            'wordpress_admin_username' => $credentials['wp_admin_username'],
        ]);

        return $credentials;
    }

    protected function prepareCredentials(Site $site): array
    {
        $base = preg_replace('/[^a-z0-9]/', '', strtolower($site->subdomain));
        $base = substr($base, 0, 4);

        $siteId = (string) $site->id;
        $siteSuffix = substr(str_pad($siteId, 4, '0', STR_PAD_LEFT), -4);

        $dbName = 'wp' . $base . $siteSuffix;
        $dbUser = 'db' . $base . $siteSuffix;

        $dbName = substr($dbName, 0, 8);
        $dbUser = substr($dbUser, 0, 8);

        $wpAdminUsername = 'admin_' . substr($base . $siteSuffix, 0, 8);
        $wpAdminUsername = substr($wpAdminUsername, 0, 20);

        return [
            'hestia_password' => Str::password(20, true, true, true, false),
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => Str::password(24, true, true, true, false),
            'wp_admin_username' => $wpAdminUsername,
            'wp_admin_password' => Str::password(20, true, true, true, false),
        ];
    }

    protected function hasSuccessfulLog(Site $site, string $action): bool
    {
        return $site->provisioningLogs()
            ->where('action', $action)
            ->where('status', 'success')
            ->exists();
    }

    protected function log(Site $site, string $action, string $status, string $message, array $context = []): void
    {
        $site->provisioningLogs()->create([
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
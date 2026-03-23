<?php

namespace App\Services\Infrastructure;

use App\Models\Site;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

class HestiaBillingService
{
    protected function ssh(): SSH2
    {
        $host = (string) config('services.hestia.host');
        $port = (int) config('services.hestia.port', 22);
        $user = (string) config('services.hestia.user');
        $timeout = (int) config('services.hestia.timeout', 30);

        $ssh = new SSH2($host, $port);
        $ssh->setTimeout($timeout);

        $privateKeyPath = config('services.hestia.private_key_path');
        $password = config('services.hestia.password');

        if ($privateKeyPath && file_exists($privateKeyPath)) {
            $keyContents = file_get_contents($privateKeyPath);

            if ($keyContents === false) {
                throw new RuntimeException('Unable to read Hestia private key file.');
            }

            $key = PublicKeyLoader::loadPrivateKey($keyContents);

            if (! $ssh->login($user, $key)) {
                throw new RuntimeException('Unable to login to Hestia via SSH key.');
            }

            return $ssh;
        }

        if ($password && $ssh->login($user, $password)) {
            return $ssh;
        }

        throw new RuntimeException('Unable to login to Hestia. Check SSH key or password config.');
    }

    public function suspendSite(Site $site): void
    {
        $username = $site->hestia_username;
        $domain = $site->hestia_domain ?: $site->fqdn;

        if (! $username || ! $domain) {
            throw new RuntimeException('Site is missing Hestia username or domain.');
        }

        $ssh = $this->ssh();
        $command = sprintf(
            'v-suspend-web-domain %s %s yes',
            escapeshellarg($username),
            escapeshellarg($domain)
        );

        $output = $ssh->exec($command);
        $exitStatus = $ssh->getExitStatus();

        if ($exitStatus !== 0 && ! str_contains(strtolower($output), 'already')) {
            throw new RuntimeException('Hestia suspend failed: ' . trim($output));
        }
    }

    public function unsuspendSite(Site $site): void
    {
        $username = $site->hestia_username;
        $domain = $site->hestia_domain ?: $site->fqdn;

        if (! $username || ! $domain) {
            throw new RuntimeException('Site is missing Hestia username or domain.');
        }

        $ssh = $this->ssh();
        $command = sprintf(
            'v-unsuspend-web-domain %s %s yes',
            escapeshellarg($username),
            escapeshellarg($domain)
        );

        $output = $ssh->exec($command);
        $exitStatus = $ssh->getExitStatus();

        if ($exitStatus !== 0 && ! str_contains(strtolower($output), 'already')) {
            throw new RuntimeException('Hestia unsuspend failed: ' . trim($output));
        }
    }
}
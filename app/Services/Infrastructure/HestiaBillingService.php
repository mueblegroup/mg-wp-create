<?php

namespace App\Services\Infrastructure;

use App\Models\Site;
use phpseclib3\Net\SSH2;
use RuntimeException;

class HestiaBillingService
{
    protected function ssh(): SSH2
    {
        $ssh = new SSH2(config('services.hestia.host'), config('services.hestia.port', 22));

        if (! $ssh->login(config('services.hestia.user'), config('services.hestia.password'))) {
            throw new RuntimeException('Unable to login to Hestia via SSH.');
        }

        $ssh->setTimeout(config('services.hestia.timeout', 30));

        return $ssh;
    }

    public function suspendSite(Site $site): void
    {
        $username = $site->hestia_username;
        $domain = $site->hestia_domain ?: $site->fqdn;

        if (! $username || ! $domain) {
            throw new RuntimeException('Site is missing Hestia username or domain.');
        }

        $ssh = $this->ssh();
        $output = $ssh->exec(sprintf('v-suspend-web-domain %s %s yes', escapeshellarg($username), escapeshellarg($domain)));

        if ($ssh->getExitStatus() !== 0 && ! str_contains(strtolower($output), 'already')) {
            throw new RuntimeException('Hestia suspend failed: ' . $output);
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
        $output = $ssh->exec(sprintf('v-unsuspend-web-domain %s %s yes', escapeshellarg($username), escapeshellarg($domain)));

        if ($ssh->getExitStatus() !== 0 && ! str_contains(strtolower($output), 'already')) {
            throw new RuntimeException('Hestia unsuspend failed: ' . $output);
        }
    }
}
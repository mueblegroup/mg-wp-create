<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\HestiaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifySuspendedSitesCommand extends Command
{
    protected $signature = 'sites:verify-suspensions 
                            {--limit=50 : Maximum sites to check per run}
                            {--force : Check all suspended sites even if recently checked}';

    protected $description = 'Verify that locally suspended sites are actually suspended on Hestia, and retry suspension if needed.';

    public function handle(HestiaService $hestia): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $query = Site::query()
            ->where('status', Site::STATUS_SUSPENDED)
            ->whereNotNull('hestia_username')
            ->where(function ($query) {
                $query->whereNull('suspension_verified_at')
                    ->orWhereColumn('suspension_verified_at', '<', 'suspended_at');
            })
            ->orderByRaw('suspension_last_checked_at IS NULL DESC')
            ->orderBy('suspension_last_checked_at');

        if (! $force) {
            $query->where(function ($query) {
                $query->whereNull('suspension_last_checked_at')
                    ->orWhere('suspension_last_checked_at', '<=', now()->subMinutes(5));
            });
        }

        $sites = $query->limit($limit)->get();

        if ($sites->isEmpty()) {
            $this->info('No suspended sites need verification.');

            return self::SUCCESS;
        }

        foreach ($sites as $site) {
            $this->verifySite($site, $hestia);
        }

        return self::SUCCESS;
    }

    protected function verifySite(Site $site, HestiaService $hestia): void
    {
        $site->forceFill([
            'suspension_last_checked_at' => now(),
        ])->save();

        try {
            $username = (string) $site->hestia_username;
            $domain = (string) ($site->hestia_domain ?: $site->fqdn);

            $isUserSuspended = $hestia->isUserSuspended($username);

            $isDomainSuspended = false;

            if ($domain !== '') {
                try {
                    $isDomainSuspended = $hestia->isWebDomainSuspended($username, $domain);
                } catch (Throwable $domainCheckException) {
                    Log::warning('Domain suspension check failed, falling back to user suspension status.', [
                        'site_id' => $site->id,
                        'username' => $username,
                        'domain' => $domain,
                        'error' => $domainCheckException->getMessage(),
                    ]);
                }
            }

            if ($isUserSuspended || $isDomainSuspended) {
                $site->forceFill([
                    'suspension_verified_at' => now(),
                    'suspension_last_error' => null,
                ])->save();

                $this->info("Verified suspended: {$site->id} {$site->fqdn}");

                return;
            }

            Log::warning('Site marked suspended locally but not suspended on Hestia. Retrying suspension.', [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
                'hestia_username' => $username,
                'hestia_domain' => $domain,
            ]);

            $hestia->suspendUser($username);

            $site->forceFill([
                'suspension_attempts' => ((int) $site->suspension_attempts) + 1,
                'suspension_verified_at' => now(),
                'suspension_last_error' => null,
            ])->save();

            $this->warn("Re-suspended and verified: {$site->id} {$site->fqdn}");
        } catch (Throwable $e) {
            $site->forceFill([
                'suspension_attempts' => ((int) $site->suspension_attempts) + 1,
                'suspension_last_failed_at' => now(),
                'suspension_last_error' => $e->getMessage(),
            ])->save();

            Log::error('Suspension verification failed.', [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
                'hestia_username' => $site->hestia_username,
                'error' => $e->getMessage(),
            ]);

            $this->error("Failed verifying site {$site->id}: {$e->getMessage()}");
        }
    }
}
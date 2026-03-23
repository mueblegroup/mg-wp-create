<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProvisionSiteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(public int $siteId)
    {
    }

    public function handle(SiteProvisioningService $siteProvisioningService): void
    {
        $site = Site::with(['plan', 'theme', 'user'])->find($this->siteId);

        if (! $site) {
            return;
        }

        if (! $site->isProvisionable() && $site->status !== Site::STATUS_PROVISIONING) {
            return;
        }

        $siteProvisioningService->provision($site);
    }

    public function failed(Throwable $exception): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $site->update([
            'status' => Site::STATUS_FAILED,
            'provisioning_error' => $exception->getMessage(),
        ]);

        $site->provisioningLogs()->create([
            'action' => 'provisioning_failed',
            'status' => 'error',
            'message' => $exception->getMessage(),
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
            ],
        ]);
    }
}
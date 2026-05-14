<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\Subscription;
use App\Services\Infrastructure\HestiaBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UnsuspendSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public int $siteId)
    {
    }

    public function handle(HestiaBillingService $service): void
    {
        $site = Site::findOrFail($this->siteId);

        $site->provisioningLogs()->create([
            'action' => 'site_unsuspend_started',
            'status' => 'info',
            'message' => 'Site unsuspension job started.',
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
                'hestia_username' => $site->hestia_username,
                'hestia_domain' => $site->hestia_domain,
            ],
        ]);

        $result = $service->unsuspendSite($site);

        $site->refresh();

        $site->update([
            'status' => Site::STATUS_ACTIVE,
            'billing_status' => Subscription::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        if ($site->subscription) {
            $site->subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'suspended_at' => null,
                'notes' => null,
            ]);
        }

        $site->provisioningLogs()->create([
            'action' => 'site_unsuspend_completed',
            'status' => 'success',
            'message' => 'Site unsuspended successfully.',
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
                'result' => $result,
            ],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $site->provisioningLogs()->create([
            'action' => 'site_unsuspend_failed',
            'status' => 'error',
            'message' => $exception->getMessage(),
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
            ],
        ]);
    }
}
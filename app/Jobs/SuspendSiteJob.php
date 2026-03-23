<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\Infrastructure\HestiaBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SuspendSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $siteId) {}

    public function handle(HestiaBillingService $service): void
    {
        $site = Site::findOrFail($this->siteId);
        $service->suspendSite($site);
    }
}
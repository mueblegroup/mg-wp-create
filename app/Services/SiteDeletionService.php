<?php

namespace App\Services;

use App\Exceptions\ProvisioningException;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Throwable;

class SiteDeletionService
{
    public function __construct(
        protected HestiaService $hestiaService,
    ) {
    }

    public function delete(Site $site): void
    {
        $site->loadMissing(['subscription', 'domains']);

        if ($site->status === Site::STATUS_PROVISIONING) {
            throw new ProvisioningException('This site is currently provisioning and cannot be deleted right now.');
        }

        $this->log($site, 'site_deletion_started', 'info', 'Site deletion process started.', [
            'site_id' => $site->id,
            'fqdn' => $site->fqdn,
            'hestia_username' => $site->hestia_username,
        ]);

        if (filled($site->hestia_username) && filled($site->fqdn)) {
            try {
                $response = $this->hestiaService->deleteDomain($site->hestia_username, $site->fqdn);

                $this->log($site, 'hestia_domain_deleted', 'success', 'Hestia domain deleted.', [
                    'response' => $response,
                    'domain' => $site->fqdn,
                ]);
            } catch (Throwable $e) {
                $this->log($site, 'hestia_domain_delete_failed', 'error', $e->getMessage(), [
                    'domain' => $site->fqdn,
                ]);

                throw $e;
            }

            try {
                $response = $this->hestiaService->deleteUser($site->hestia_username);

                $this->log($site, 'hestia_user_deleted', 'success', 'Hestia user deleted.', [
                    'response' => $response,
                    'username' => $site->hestia_username,
                ]);
            } catch (Throwable $e) {
                $this->log($site, 'hestia_user_delete_failed', 'error', $e->getMessage(), [
                    'username' => $site->hestia_username,
                ]);

                throw $e;
            }
        }

        DB::transaction(function () use ($site) {
            if ($site->subscription) {
                $site->subscription->delete();
            }

            $site->paymentTransactions()->delete();
            $site->domains()->delete();
            $site->provisioningLogs()->delete();

            $site->delete();
        });
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
<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HitPayWebhookProcessor
{
    public function __construct(
        protected HitPayClient $client,
        protected BillingService $billingService,
    ) {}

    public function ingest(string $rawPayload, array $headers): WebhookLog
    {
        $signature = $headers['hitpay-signature'][0] ?? $headers['Hitpay-Signature'][0] ?? null;
        $eventType = $headers['hitpay-event-type'][0] ?? $headers['Hitpay-Event-Type'][0] ?? null;
        $eventObject = $headers['hitpay-event-object'][0] ?? $headers['Hitpay-Event-Object'][0] ?? null;
        $payloadHash = hash('sha256', $rawPayload);

        return WebhookLog::firstOrCreate(
            ['payload_hash' => $payloadHash],
            [
                'provider' => 'hitpay',
                'event_type' => $eventType,
                'event_object' => $eventObject,
                'signature' => $signature,
                'raw_payload' => $rawPayload,
                'headers' => $headers,
                'is_valid' => $this->client->validateWebhook($rawPayload, $signature),
            ]
        );
    }

    public function process(WebhookLog $log): void
    {
        if ($log->is_processed) {
            return;
        }

        if (! $log->is_valid) {
            throw new RuntimeException('Invalid HitPay webhook signature.');
        }

        $payload = json_decode($log->raw_payload, true, 512, JSON_THROW_ON_ERROR);
        $reference = Arr::get($payload, 'reference');

        if (! $reference) {
            $log->update(['processing_error' => 'Missing subscription reference in payload.']);
            return;
        }

        preg_match('/sub_(\d+)_inv_(\d+)/', $reference, $matches);
        $subscriptionId = isset($matches[1]) ? (int) $matches[1] : null;
        $invoiceId = isset($matches[2]) ? (int) $matches[2] : null;

        if (! $subscriptionId) {
            throw new RuntimeException('Unable to resolve subscription from reference: ' . $reference);
        }

        $subscription = Subscription::findOrFail($subscriptionId);
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

        DB::transaction(function () use ($log, $payload, $subscription, $invoice) {
            if ($log->event_type === 'charge.created' && ($payload['status'] ?? null) === 'succeeded') {
                $normalized = $this->client->normalizeChargePayload($payload);
                $this->billingService->handleSuccessfulPayment($subscription, $invoice ?? $this->makeFallbackInvoice($subscription, $payload), $normalized);
            } elseif ($log->event_type === 'recurring_billing.subscription_updated') {
                $status = Arr::get($payload, 'status');

                if (in_array($status, ['cancelled', 'expired'], true)) {
                    $subscription->update([
                        'status' => $status,
                        $status . '_at' => now(),
                        'meta' => array_merge($subscription->meta ?? [], ['last_subscription_update' => $payload]),
                    ]);
                }
            } else {
                $subscription->update([
                    'meta' => array_merge($subscription->meta ?? [], ['last_webhook_payload' => $payload]),
                ]);
            }

            $log->update([
                'is_processed' => true,
                'processed_at' => now(),
                'processing_error' => null,
            ]);
        });
    }

    protected function makeFallbackInvoice(Subscription $subscription, array $payload): Invoice
    {
        return Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'site_id' => $subscription->site_id,
            'invoice_number' => 'INV-FB-' . now()->format('YmdHis') . '-' . $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'currency' => strtoupper((string) ($payload['currency'] ?? config('services.hitpay.currency', 'MYR'))),
            'amount' => $payload['amount'] ?? $subscription->amount,
            'due_at' => now(),
        ]);
    }
}
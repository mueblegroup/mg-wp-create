<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HitPayWebhookProcessor
{
    public function __construct(
        protected HitPayClient $client,
        protected BillingService $billingService,
    ) {
    }

    public function ingest(string $rawPayload, array $headers): WebhookLog
    {
        $normalizedHeaders = $this->normalizeHeaders($headers);

        $contentType = strtolower((string) Arr::get($normalizedHeaders, 'content-type.0', ''));
        $headerSignature = Arr::get($normalizedHeaders, 'hitpay-signature.0');
        $eventType = Arr::get($normalizedHeaders, 'hitpay-event-type.0');
        $eventObject = Arr::get($normalizedHeaders, 'hitpay-event-object.0');

        $payloadHash = hash('sha256', $rawPayload);

        $parsedPayload = [];
        $signature = $headerSignature;
        $isValid = false;
        $payloadFormat = 'unknown';

        if (str_contains($contentType, 'application/json') || $this->looksLikeJson($rawPayload)) {
            $payloadFormat = 'json';
            $parsedPayload = json_decode($rawPayload, true) ?: [];

            $isValid = $this->client->validateJsonWebhook($rawPayload, $headerSignature);

            $eventType = $eventType
                ?: Arr::get($parsedPayload, 'event')
                ?: Arr::get($parsedPayload, 'action')
                ?: 'created';

            $eventObject = $eventObject
                ?: Arr::get($parsedPayload, 'resource')
                ?: Arr::get($parsedPayload, 'relatable.type')
                ?: 'charge';
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $payloadFormat = 'form';

            parse_str($rawPayload, $parsedPayload);

            $signature = $parsedPayload['hmac'] ?? null;
            $isValid = $this->client->validateFormWebhook($rawPayload, $signature);

            $eventType = $eventType ?: 'charge.created';
            $eventObject = $eventObject ?: 'recurring_billing';
        }

        if (! $isValid) {
            Log::warning('HitPay webhook failed HMAC validation.', [
                'format' => $payloadFormat,
                'content_type' => $contentType,
                'event_type' => $eventType,
                'event_object' => $eventObject,
                'signature_exists' => ! empty($signature),
                'payload_hash' => $payloadHash,
                'payload_length' => strlen($rawPayload),
            ]);
        }

        return WebhookLog::updateOrCreate(
            ['payload_hash' => $payloadHash],
            [
                'provider' => 'hitpay',
                'event_type' => $eventType,
                'event_object' => $eventObject,
                'signature' => $signature,
                'raw_payload' => $rawPayload,
                'headers' => array_merge($normalizedHeaders, [
                    '_parsed' => $parsedPayload,
                    '_format' => $payloadFormat,
                ]),
                'is_valid' => $isValid,
            ]
        );
    }

    public function process(WebhookLog $log): void
    {
        if ($log->is_processed) {
            return;
        }

        if (! $log->is_valid) {
            $log->update([
                'processing_error' => 'Invalid HMAC',
                'is_processed' => true,
                'processed_at' => now(),
            ]);

            \Log::warning('HitPay webhook ignored because HMAC validation failed.', [
                'webhook_log_id' => $log->id,
                'event_type' => $log->event_type,
                'event_object' => $log->event_object,
                'signature' => $log->signature,
            ]);

            return;
        }

        $payload = $this->extractPayload($log);

        $reference = $this->extractReference($payload);

        if (! $reference) {
            $log->update([
                'processing_error' => 'Missing reference in payload.',
            ]);

            Log::warning('HitPay webhook missing reference.', [
                'webhook_log_id' => $log->id,
                'event_type' => $log->event_type,
                'event_object' => $log->event_object,
                'payload' => $payload,
            ]);

            return;
        }

        preg_match('/sub_(\d+)_inv_(\d+)/i', $reference, $matches);

        $subscriptionId = isset($matches[1]) ? (int) $matches[1] : null;
        $invoiceId = isset($matches[2]) ? (int) $matches[2] : null;

        if (! $subscriptionId) {
            $log->update([
                'processing_error' => 'Unable to resolve subscription from reference: ' . $reference,
            ]);

            throw new RuntimeException('Unable to resolve subscription from reference: ' . $reference);
        }

        $subscription = Subscription::findOrFail($subscriptionId);
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

        DB::transaction(function () use ($log, $payload, $subscription, $invoice, $reference) {
            $status = strtolower((string) Arr::get($payload, 'status'));
            $eventType = strtolower((string) ($log->event_type ?? ''));
            $eventObject = strtolower((string) ($log->event_object ?? ''));

            $normalized = $this->client->normalizeChargePayload($payload);

            if (
                in_array($status, ['succeeded', 'completed', 'paid'], true)
                || in_array($eventType, ['created', 'completed', 'charge.created', 'payment_request.completed'], true)
            ) {
                if (in_array($status, ['succeeded', 'completed', 'paid'], true)) {
                    $this->billingService->handleSuccessfulPayment(
                        $subscription,
                        $invoice ?? $this->makeFallbackInvoice($subscription, $payload),
                        $normalized
                    );
                }
            } elseif (
                in_array($status, ['failed', 'canceled', 'cancelled'], true)
                || in_array($eventType, ['charge.failed', 'failed'], true)
            ) {
                $this->billingService->handleFailedPayment(
                    $subscription,
                    $invoice,
                    $payload,
                    Arr::get($payload, 'failed_reason_message')
                        ?? Arr::get($payload, 'failed_reason')
                        ?? Arr::get($payload, 'status')
                );
            } elseif (
                $eventType === 'recurring_billing.subscription_updated'
                || $eventObject === 'recurring_billing'
            ) {
                $providerStatus = strtolower((string) Arr::get($payload, 'status'));

                if (in_array($providerStatus, ['cancelled', 'canceled', 'expired'], true)) {
                    $subscription->update([
                        'status' => $providerStatus === 'canceled' ? 'cancelled' : $providerStatus,
                        ($providerStatus === 'canceled' ? 'cancelled' : $providerStatus) . '_at' => now(),
                        'meta' => array_merge($subscription->meta ?? [], [
                            'last_subscription_update' => $payload,
                        ]),
                    ]);
                }
            }

            $log->update([
                'is_processed' => true,
                'processed_at' => now(),
                'processing_error' => null,
            ]);

            Log::info('HitPay webhook processed.', [
                'webhook_log_id' => $log->id,
                'reference' => $reference,
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice?->id,
                'event_type' => $eventType,
                'event_object' => $eventObject,
                'status' => $status,
            ]);
        });
    }

    protected function extractPayload(WebhookLog $log): array
    {
        $headers = is_array($log->headers) ? $log->headers : [];
        $parsed = $headers['_parsed'] ?? null;

        if (is_array($parsed) && ! empty($parsed)) {
            return $parsed;
        }

        $rawPayload = (string) $log->raw_payload;

        if ($this->looksLikeJson($rawPayload)) {
            return json_decode($rawPayload, true) ?: [];
        }

        parse_str($rawPayload, $payload);

        return is_array($payload) ? $payload : [];
    }

    protected function extractReference(array $payload): ?string
    {
        return Arr::get($payload, 'reference')
            ?? Arr::get($payload, 'reference_number')
            ?? Arr::get($payload, 'metadata.reference')
            ?? Arr::get($payload, 'data.reference')
            ?? Arr::get($payload, 'data.reference_number')
            ?? Arr::get($payload, 'relatable.reference')
            ?? Arr::get($payload, 'relatable.business_charge.reference')
            ?? Arr::get($payload, 'relatable.recurring_billing.reference')
            ?? Arr::get($payload, 'recurring_billing.reference')
            ?? Arr::get($payload, 'business_charge.reference');
    }

    protected function makeFallbackInvoice(Subscription $subscription, array $payload): Invoice
    {
        return Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'site_id' => $subscription->site_id,
            'invoice_number' => 'INV-FB-' . now()->format('YmdHis') . '-' . $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'currency' => strtoupper((string) (
                Arr::get($payload, 'currency')
                ?? config('services.hitpay.currency', 'MYR')
            )),
            'amount' => Arr::get($payload, 'amount') ?? $subscription->amount,
            'due_at' => now(),
        ]);
    }

    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = is_array($value) ? $value : [$value];
        }

        return $normalized;
    }

    protected function looksLikeJson(string $payload): bool
    {
        $payload = trim($payload);

        return str_starts_with($payload, '{') || str_starts_with($payload, '[');
    }
}
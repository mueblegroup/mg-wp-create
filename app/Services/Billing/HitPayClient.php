<?php

namespace App\Services\Billing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HitPayClient
{
    public function http(): PendingRequest
    {
        return Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->withHeaders([
                'X-BUSINESS-API-KEY' => config('services.hitpay.api_key'),
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
    }

    public function createPlan(array $payload): array
    {
        $response = $this->http()->post(
            rtrim(config('services.hitpay.base_url'), '/') . '/subscription-plan',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay create plan failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function createRecurringBilling(array $payload): array
    {
        $response = $this->http()->post(
            rtrim(config('services.hitpay.base_url'), '/') . '/recurring-billing',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay create recurring billing failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function createPaymentRequest(array $payload): array
    {
        $response = $this->http()->post(
            rtrim(config('services.hitpay.base_url'), '/') . '/payment-requests',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay payment request failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    public function validateJsonWebhook(string $rawPayload, ?string $signature): bool
    {
        $salt = (string) config('services.hitpay.webhook_salt');

        if (! $signature || $salt === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $rawPayload, $salt);

        $valid = hash_equals($computed, $signature);

        if (! $valid) {
            Log::warning('HitPay JSON webhook HMAC mismatch.', [
                'received_signature' => $signature,
                'computed_signature' => $computed,
                'payload_length' => strlen($rawPayload),
                'salt_exists' => $salt !== '',
            ]);
        }

        return $valid;
    }

    public function validateFormWebhook(string $rawPayload, ?string $signature): bool
    {
        $salt = (string) config('services.hitpay.webhook_salt');

        if (! $signature || $salt === '') {
            return false;
        }

        $payloadWithoutHmac = preg_replace('/(^|&)hmac=[^&]*(&|$)/', '$1', $rawPayload);
        $payloadWithoutHmac = trim((string) $payloadWithoutHmac, '&');

        $computed = hash_hmac('sha256', $payloadWithoutHmac, $salt);

        $valid = hash_equals($computed, $signature);

        if (! $valid) {
            Log::warning('HitPay form webhook HMAC mismatch.', [
                'received_signature' => $signature,
                'computed_signature' => $computed,
                'payload_without_hmac' => $payloadWithoutHmac,
                'payload_length' => strlen($rawPayload),
                'salt_exists' => $salt !== '',
            ]);
        }

        return $valid;
    }

    public function normalizeChargePayload(array $payload): array
    {
        return [
            'provider_charge_id' => Arr::get($payload, 'id')
                ?? Arr::get($payload, 'payment_id')
                ?? Arr::get($payload, 'charge.id')
                ?? Arr::get($payload, 'data.id'),

            'provider_subscription_id' => Arr::get($payload, 'recurring_billing_id')
                ?? Arr::get($payload, 'business_recurring_billing_id')
                ?? Arr::get($payload, 'relatable.id')
                ?? Arr::get($payload, 'relatable.recurring_billing.id')
                ?? Arr::get($payload, 'relatable.business_charge.id'),

            'status' => Arr::get($payload, 'status')
                ?? Arr::get($payload, 'data.status'),

            'amount' => Arr::get($payload, 'amount')
                ?? Arr::get($payload, 'data.amount'),

            'currency' => strtoupper((string) (
                Arr::get($payload, 'currency')
                ?? Arr::get($payload, 'data.currency')
                ?? 'MYR'
            )),

            'customer_email' => Arr::get($payload, 'customer.email')
                ?? Arr::get($payload, 'customer_email')
                ?? Arr::get($payload, 'email')
                ?? Arr::get($payload, 'data.customer.email'),

            'customer_name' => Arr::get($payload, 'customer.name')
                ?? Arr::get($payload, 'customer_name')
                ?? Arr::get($payload, 'name')
                ?? Arr::get($payload, 'data.customer.name'),

            'brand' => Arr::get($payload, 'payment_provider.charge.details.brand')
                ?? Arr::get($payload, 'card_brand'),

            'last4' => Arr::get($payload, 'payment_provider.charge.details.last4')
                ?? Arr::get($payload, 'card_last_4'),

            'country' => Arr::get($payload, 'payment_provider.charge.details.country_code')
                ?? Arr::get($payload, 'country'),

            'reference' => Arr::get($payload, 'reference')
                ?? Arr::get($payload, 'reference_number')
                ?? Arr::get($payload, 'metadata.reference')
                ?? Arr::get($payload, 'data.reference')
                ?? Arr::get($payload, 'data.reference_number')
                ?? Arr::get($payload, 'relatable.reference')
                ?? Arr::get($payload, 'relatable.business_charge.reference')
                ?? Arr::get($payload, 'relatable.recurring_billing.reference')
                ?? Arr::get($payload, 'recurring_billing.reference')
                ?? Arr::get($payload, 'business_charge.reference'),

            'raw' => $payload,
        ];
    }
}
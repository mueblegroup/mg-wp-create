<?php

namespace App\Services\Billing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
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
            config('services.hitpay.base_url') . '/subscription-plan',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay create plan failed: ' . $response->body());
        }

        return $response->json();
    }

    public function createRecurringBilling(array $payload): array
    {
        $response = $this->http()->post(
            config('services.hitpay.base_url') . '/recurring-billing',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay create recurring billing failed: ' . $response->body());
        }

        return $response->json();
    }

    public function createPaymentRequest(array $payload): array
    {
        $response = $this->http()->post(
            config('services.hitpay.base_url') . '/payment-requests',
            $payload
        );

        if ($response->failed()) {
            throw new RuntimeException('HitPay payment request failed: ' . $response->body());
        }

        return $response->json();
    }

    public function validateJsonWebhook(string $rawPayload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $salt = (string) config('services.hitpay.webhook_salt');

        if ($salt === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $rawPayload, $salt);

        return hash_equals($computed, $signature);
    }

    public function validateFormWebhook(string $rawPayload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $salt = (string) config('services.hitpay.webhook_salt');

        if ($salt === '') {
            return false;
        }

        // Remove only the trailing hmac pair from the raw payload exactly as received.
        $payloadWithoutHmac = preg_replace('/(?:^|&)hmac=[^&]*$/', '', $rawPayload);

        if ($payloadWithoutHmac === null) {
            return false;
        }

        $computed = hash_hmac('sha256', $payloadWithoutHmac, $salt);

        return hash_equals($computed, $signature);
    }

public function normalizeChargePayload(array $payload): array
{
    return [
        'provider_charge_id' => Arr::get($payload, 'id')
            ?? Arr::get($payload, 'payment_id'),
        'provider_subscription_id' => Arr::get($payload, 'relatable.business_charge.id')
            ?? Arr::get($payload, 'recurring_billing_id'),
        'status' => Arr::get($payload, 'status'),
        'amount' => Arr::get($payload, 'amount'),
        'currency' => strtoupper((string) Arr::get($payload, 'currency', 'MYR')),
        'customer_email' => Arr::get($payload, 'customer.email')
            ?? Arr::get($payload, 'customer_email'),
        'customer_name' => Arr::get($payload, 'customer.name')
            ?? Arr::get($payload, 'customer_name'),
        'brand' => Arr::get($payload, 'payment_provider.charge.details.brand'),
        'last4' => Arr::get($payload, 'payment_provider.charge.details.last4'),
        'country' => Arr::get($payload, 'payment_provider.charge.details.country_code')
            ?? Arr::get($payload, 'payment_provider.charge.details.country_code'),
        'reference' => Arr::get($payload, 'reference')
            ?? Arr::get($payload, 'reference_number')
            ?? Arr::get($payload, 'relatable.business_charge.reference'),
        'raw' => $payload,
    ];
}
}
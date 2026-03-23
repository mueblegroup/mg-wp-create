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

    public function validateWebhook(string $rawPayload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $salt = (string) config('services.hitpay.salt');
        $computed = hash_hmac('sha256', $rawPayload, $salt);

        return hash_equals($computed, $signature);
    }

    public function normalizeChargePayload(array $payload): array
    {
        return [
            'provider_charge_id' => Arr::get($payload, 'id'),
            'status' => Arr::get($payload, 'status'),
            'amount' => Arr::get($payload, 'amount'),
            'currency' => strtoupper((string) Arr::get($payload, 'currency', 'MYR')),
            'customer_email' => Arr::get($payload, 'customer.email'),
            'customer_name' => Arr::get($payload, 'customer.name'),
            'brand' => Arr::get($payload, 'payment_provider.charge.details.brand'),
            'last4' => Arr::get($payload, 'payment_provider.charge.details.last4'),
            'country' => Arr::get($payload, 'payment_provider.charge.details.country_code'),
            'reference' => Arr::get($payload, 'reference'),
            'raw' => $payload,
        ];
    }
}
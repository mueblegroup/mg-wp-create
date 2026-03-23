<?php

namespace App\Services;

class HitPayService
{
    public function createSubscription(array $payload): array
    {
        return [
            'success' => true,
            'message' => 'Stub: HitPay subscription creation not implemented yet.',
            'payload' => $payload,
        ];
    }

    public function verifyWebhook(array $payload, ?string $signature = null): bool
    {
        return true;
    }
}
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Billing\HitPayWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HitPayWebhookController extends Controller
{
    
    public function __invoke(Request $request, HitPayWebhookProcessor $processor): JsonResponse
    {
        \Log::info('HitPay webhook reached', [
            'content_type' => $request->header('Content-Type'),
            'hitpay_signature' => $request->header('Hitpay-Signature'),
            'hitpay_event_type' => $request->header('Hitpay-Event-Type'),
            'hitpay_event_object' => $request->header('Hitpay-Event-Object'),
            'payload' => $request->getContent(),
        ]);
        
        $rawPayload = $request->getContent();
        $headers = $request->headers->all();

        try {
            $log = $processor->ingest($rawPayload, $headers);
            $processor->process($log);

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
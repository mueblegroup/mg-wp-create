<?php

namespace App\Services\Billing;

use App\Jobs\ProvisionSiteJob;
use App\Jobs\SuspendSiteJob;
use App\Jobs\UnsuspendSiteJob;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        protected HitPayClient $hitPayClient,
        protected SubscriptionStateMachine $stateMachine,
    ) {
    }

    public function startCheckout(User $user, Plan $plan, Site $site): array
    {
        return DB::transaction(function () use ($user, $plan, $site) {
            $subscription = Subscription::query()->updateOrCreate(
                ['site_id' => $site->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'hitpay',
                    'status' => Subscription::STATUS_PENDING,
                    'currency' => config('services.hitpay.currency', 'MYR'),
                    'amount' => $plan->price,
                    'billing_cycle' => $plan->billing_cycle ?? 'monthly',
                    'starts_at' => now(),
                ]
            );

            if (! $subscription->provider_plan_id) {
                $planPayload = $this->hitPayClient->createPlan([
                    'name' => $plan->name,
                    'description' => $plan->description ?? $plan->name,
                    'currency' => config('services.hitpay.currency', 'MYR'),
                    'amount' => $plan->price,
                    'cycle' => $plan->billing_cycle ?? 'monthly',
                    'reference' => 'plan_' . $plan->id,
                ]);

                $subscription->update([
                    'provider_plan_id' => $planPayload['id'] ?? null,
                    'meta' => array_merge($subscription->meta ?? [], [
                        'provider_plan_payload' => $planPayload,
                    ]),
                ]);
            }

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'site_id' => $site->id,
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                'status' => Invoice::STATUS_PENDING,
                'currency' => config('services.hitpay.currency', 'MYR'),
                'amount' => $plan->price,
                'billing_period_start' => now(),
                'billing_period_end' => ($plan->billing_cycle ?? 'monthly') === 'yearly'
                    ? now()->copy()->addYear()
                    : now()->copy()->addMonth(),
                'due_at' => now(),
            ]);

            $reference = 'sub_' . $subscription->id . '_inv_' . $invoice->id;

            $recurring = $this->hitPayClient->createRecurringBilling([
                'plan_id' => $subscription->provider_plan_id,
                'customer_email' => $user->email,
                'customer_name' => $user->name,
                'start_date' => $this->hitpayStartDate(),
                'amount' => $plan->price,
                'currency' => config('services.hitpay.currency', 'MYR'),
                'redirect_url' => config('services.hitpay.success_url'),
                'reference' => $reference,
                'send_email' => 'true',
                'webhook' => config('services.hitpay.webhook_url'),
            ]);

            $subscription->update([
                'provider_subscription_id' => $recurring['id'] ?? null,
                'provider_customer_reference' => $reference,
                'meta' => array_merge($subscription->meta ?? [], [
                    'provider_subscription_payload' => $recurring,
                ]),
            ]);

            $site->update([
                'status' => Site::STATUS_PENDING_PAYMENT,
                'billing_status' => Subscription::STATUS_PENDING,
            ]);

            return [
                'subscription' => $subscription->fresh(),
                'invoice' => $invoice,
                'checkout_url' => $recurring['url'] ?? null,
                'provider_payload' => $recurring,
            ];
        });
    }
    protected function hitpayStartDate(): string
    {
        return now('Asia/Kuala_Lumpur')->format('Y-m-d');
    }

    public function handleSuccessfulPayment(Subscription $subscription, Invoice $invoice, array $normalized): void
    {
        DB::transaction(function () use ($subscription, $invoice, $normalized) {
            $invoice->update([
                'provider_charge_id' => $normalized['provider_charge_id'],
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
                'meta' => $normalized['raw'],
            ]);

            $subscription->paymentAttempts()->updateOrCreate(
                [
                    'provider_charge_id' => $normalized['provider_charge_id'],
                ],
                [
                    'invoice_id' => $invoice->id,
                    'provider_event_type' => 'charge.created',
                    'provider_reference' => $subscription->provider_customer_reference,
                    'status' => 'succeeded',
                    'amount' => $normalized['amount'],
                    'currency' => $normalized['currency'],
                    'attempted_at' => now(),
                    'succeeded_at' => now(),
                    'payload' => $normalized['raw'],
                ]
            );

            if ($normalized['brand'] || $normalized['last4']) {
                $subscription->paymentMethods()->updateOrCreate(
                    [
                        'provider_method_id' => $normalized['provider_charge_id'],
                    ],
                    [
                        'user_id' => $subscription->user_id,
                        'provider' => 'hitpay',
                        'brand' => $normalized['brand'],
                        'last4' => $normalized['last4'],
                        'country' => $normalized['country'],
                        'status' => 'attached',
                        'is_default' => true,
                        'meta' => $normalized['raw'],
                    ]
                );
            }

            $this->stateMachine->activate($subscription, $invoice);
        });

        $subscription->refresh();
        $site = $subscription->site()->first();

        if (! $site) {
            return;
        }

        if (! $site->provisioned_at) {
            $site->update([
                'status' => Site::STATUS_PROVISIONING,
                'provisioning_error' => null,
            ]);

            $site->provisioningLogs()->create([
                'action' => 'provisioning_queued_after_payment',
                'status' => 'info',
                'message' => 'Payment completed successfully. WordPress provisioning has been queued.',
                'context' => [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'provider_charge_id' => $normalized['provider_charge_id'],
                ],
            ]);

            ProvisionSiteJob::dispatch($site->id);

            return;
        }

        if ($site->status === Site::STATUS_SUSPENDED) {
            UnsuspendSiteJob::dispatch($site->id);
            return;
        }

        $site->update([
            'status' => Site::STATUS_ACTIVE,
            'billing_status' => Subscription::STATUS_ACTIVE,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);
    }

    public function handleFailedPayment(Subscription $subscription, ?Invoice $invoice, array $payload, ?string $reason = null): void
    {
        DB::transaction(function () use ($subscription, $invoice, $payload, $reason) {
            $subscription->paymentAttempts()->create([
                'invoice_id' => $invoice?->id,
                'provider_event_type' => 'charge.failed',
                'provider_charge_id' => $payload['id'] ?? null,
                'provider_reference' => $subscription->provider_customer_reference,
                'status' => 'failed',
                'amount' => $payload['amount'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'attempted_at' => now(),
                'failed_at' => now(),
                'failure_reason' => $reason,
                'payload' => $payload,
            ]);

            $this->stateMachine->markPastDue($subscription, $invoice, $reason);
        });
    }

    public function suspendForNonPayment(Subscription $subscription, string $reason = 'Payment overdue'): void
    {
        $this->stateMachine->suspend($subscription, $reason);

        if ($subscription->site) {
            SuspendSiteJob::dispatch($subscription->site->id);
        }
    }
}
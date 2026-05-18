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
use RuntimeException;

class BillingService
{
    public function __construct(
        protected HitPayClient $hitPayClient,
        protected SubscriptionStateMachine $stateMachine,
    ) {
    }

    /**
     * Start payment for a selected plan.
     *
     * Used for:
     * - first payment after site creation
     * - upgrade/downgrade from Billing page
     *
     * Important:
     * We intentionally use HitPay payment-requests instead of recurring-billing
     * because recurring-billing is currently returning "Forbidden" for your API key/account.
     */
    public function startCheckout(User $user, Plan $plan, Site $site, ?string $purpose = null): array
    {
        return DB::transaction(function () use ($user, $plan, $site, $purpose) {
            if ((int) $site->user_id !== (int) $user->id) {
                throw new RuntimeException('Site does not belong to this user.');
            }

            $currency = $plan->currency ?? config('services.hitpay.currency', 'MYR');
            $billingCycle = $plan->billing_cycle ?? 'monthly';

            $subscription = Subscription::query()->updateOrCreate(
                ['site_id' => $site->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'hitpay',
                    'status' => Subscription::STATUS_PENDING,
                    'currency' => $currency,
                    'amount' => $plan->price,
                    'billing_cycle' => $billingCycle,
                    'starts_at' => now(),
                ]
            );

            $site->update([
                'plan_id' => $plan->id,
                'status' => $site->provisioned_at ? $site->status : Site::STATUS_PENDING_PAYMENT,
                'billing_status' => Subscription::STATUS_PENDING,
            ]);

            $invoice = $this->getOrCreatePendingInvoice(
                subscription: $subscription,
                user: $user,
                site: $site,
                amount: (float) $plan->price,
                currency: $currency,
                billingCycle: $billingCycle,
            );

            return $this->createHitPayPaymentRequest(
                user: $user,
                subscription: $subscription,
                invoice: $invoice,
                purpose: $purpose ?: 'Payment for ' . ($site->name ?? 'site subscription')
            );
        });
    }

    /**
     * One-click site payment.
     *
     * Used by site show page:
     * "Make Payment" → directly HitPay, no billing form.
     */
    public function startSitePayment(User $user, Site $site): array
    {
        return DB::transaction(function () use ($user, $site) {
            if ((int) $site->user_id !== (int) $user->id) {
                throw new RuntimeException('Site does not belong to this user.');
            }

            $site->loadMissing(['plan', 'subscription.plan']);

            $plan = $site->subscription?->plan ?: $site->plan;

            if (! $plan) {
                throw new RuntimeException('This site does not have a package selected.');
            }

            if (! $plan->is_active) {
                throw new RuntimeException('The selected package is no longer active.');
            }

            $currency = $site->subscription?->currency
                ?: $plan->currency
                ?: config('services.hitpay.currency', 'MYR');

            $billingCycle = $site->subscription?->billing_cycle
                ?: $plan->billing_cycle
                ?: 'monthly';

            $subscription = Subscription::query()->updateOrCreate(
                ['site_id' => $site->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'hitpay',
                    'status' => $site->subscription?->status ?: Subscription::STATUS_PENDING,
                    'currency' => $currency,
                    'amount' => $plan->price,
                    'billing_cycle' => $billingCycle,
                    'starts_at' => $site->subscription?->starts_at ?: now(),
                ]
            );

            $invoice = $subscription->invoices()
                ->where('status', Invoice::STATUS_PENDING)
                ->latest()
                ->first();

            if (! $invoice) {
                $invoice = $this->getOrCreatePendingInvoice(
                    subscription: $subscription,
                    user: $user,
                    site: $site,
                    amount: (float) $subscription->amount,
                    currency: $subscription->currency ?: $currency,
                    billingCycle: $subscription->billing_cycle ?: $billingCycle,
                );
            }

            return $this->createHitPayPaymentRequest(
                user: $user,
                subscription: $subscription,
                invoice: $invoice,
                purpose: $site->provisioned_at
                    ? 'Renewal payment for ' . $site->name
                    : 'First payment for ' . $site->name
            );
        });
    }

    public function startInvoicePayment(User $user, Invoice $invoice): array
    {
        return DB::transaction(function () use ($user, $invoice) {
            $invoice->load(['subscription.plan', 'site']);

            if ((int) $invoice->user_id !== (int) $user->id) {
                throw new RuntimeException('Invoice does not belong to this user.');
            }

            if ($invoice->status !== Invoice::STATUS_PENDING) {
                throw new RuntimeException('Invoice is not pending payment.');
            }

            $subscription = $invoice->subscription;

            if (! $subscription) {
                throw new RuntimeException('Invoice is missing subscription.');
            }

            return $this->createHitPayPaymentRequest(
                user: $user,
                subscription: $subscription,
                invoice: $invoice,
                purpose: 'Payment for ' . ($invoice->site?->name ?? 'site subscription')
            );
        });
    }

    protected function createHitPayPaymentRequest(
        User $user,
        Subscription $subscription,
        Invoice $invoice,
        string $purpose
    ): array {
        $reference = 'sub_' . $subscription->id . '_inv_' . $invoice->id;

        $paymentRequest = $this->hitPayClient->createPaymentRequest([
            'amount' => $invoice->amount,
            'currency' => $invoice->currency ?: config('services.hitpay.currency', 'MYR'),
            'email' => $user->email,
            'name' => $user->name,
            'purpose' => $purpose,
            'reference_number' => $reference,
            'redirect_url' => config('services.hitpay.success_url'),
            'webhook' => config('services.hitpay.webhook_url'),
        ]);

        $invoice->update([
            'provider_invoice_id' => $paymentRequest['id'] ?? $invoice->provider_invoice_id,
            'meta' => array_merge($invoice->meta ?? [], [
                'payment_request_payload' => $paymentRequest,
                'payment_reference' => $reference,
            ]),
        ]);

        $subscription->update([
            'provider_customer_reference' => $reference,
            'meta' => array_merge($subscription->meta ?? [], [
                'last_payment_request_payload' => $paymentRequest,
                'last_payment_reference' => $reference,
            ]),
        ]);

        return [
            'subscription' => $subscription->fresh(),
            'invoice' => $invoice->fresh(),
            'checkout_url' => $paymentRequest['url']
                ?? $paymentRequest['payment_url']
                ?? $paymentRequest['checkout_url']
                ?? null,
            'provider_payload' => $paymentRequest,
        ];
    }

    protected function getOrCreatePendingInvoice(
        Subscription $subscription,
        User $user,
        Site $site,
        float $amount,
        string $currency,
        string $billingCycle
    ): Invoice {
        $existing = $subscription->invoices()
            ->where('status', Invoice::STATUS_PENDING)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'status' => Invoice::STATUS_PENDING,
            'currency' => $currency,
            'amount' => $amount,
            'billing_period_start' => now(),
            'billing_period_end' => $billingCycle === 'yearly'
                ? now()->copy()->addYear()
                : now()->copy()->addMonth(),
            'due_at' => now(),
        ]);
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
                'meta' => array_merge($invoice->meta ?? [], [
                    'successful_payment_payload' => $normalized['raw'],
                ]),
            ]);

            $subscription->paymentAttempts()->updateOrCreate(
                [
                    'provider_charge_id' => $normalized['provider_charge_id'],
                ],
                [
                    'invoice_id' => $invoice->id,
                    'provider_event_type' => 'payment.completed',
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
                'provider_event_type' => 'payment.failed',
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
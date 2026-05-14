<?php

namespace App\Console\Commands\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\BillingService;
use App\Services\Billing\SubscriptionStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckOverdueSubscriptions extends Command
{
    protected $signature = 'billing:check-overdue-subscriptions';

    protected $description = 'Check active subscriptions whose next billing date has passed and suspend overdue sites after grace period.';

    public function handle(
        SubscriptionStateMachine $stateMachine,
        BillingService $billingService
    ): int {
        $this->info('Checking subscriptions...');

        $this->markActiveSubscriptionsPastDue($stateMachine);
        $this->suspendExpiredGracePeriodSubscriptions($billingService);

        $this->info('Done.');

        return self::SUCCESS;
    }

    protected function markActiveSubscriptionsPastDue(SubscriptionStateMachine $stateMachine): void
    {
        $subscriptions = Subscription::query()
            ->with(['site', 'plan', 'user'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $invoice = $this->createRenewalInvoice($subscription);

            $reason = 'Subscription renewal payment is due.';

            $stateMachine->markPastDue($subscription, $invoice, $reason);

            if ($subscription->site) {
                $subscription->site->provisioningLogs()->create([
                    'action' => 'subscription_marked_past_due',
                    'status' => 'info',
                    'message' => 'Subscription renewal date has passed. Customer must make payment before grace period ends.',
                    'context' => [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'next_billing_at' => optional($subscription->next_billing_at)->toDateTimeString(),
                        'grace_ends_at' => optional($subscription->fresh()->grace_ends_at)->toDateTimeString(),
                    ],
                ]);
            }

            $this->line("Marked subscription #{$subscription->id} as past due.");
        }
    }

    protected function suspendExpiredGracePeriodSubscriptions(BillingService $billingService): void
    {
        $subscriptions = Subscription::query()
            ->with(['site'])
            ->whereIn('status', [
                Subscription::STATUS_PAST_DUE,
                Subscription::STATUS_GRACE_PERIOD,
            ])
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $billingService->suspendForNonPayment(
                $subscription,
                'Subscription payment overdue. Grace period ended.'
            );

            if ($subscription->site) {
                $subscription->site->provisioningLogs()->create([
                    'action' => 'subscription_grace_period_ended',
                    'status' => 'error',
                    'message' => 'Subscription grace period ended. Site suspension has been queued.',
                    'context' => [
                        'subscription_id' => $subscription->id,
                        'site_id' => $subscription->site->id,
                        'grace_ends_at' => optional($subscription->grace_ends_at)->toDateTimeString(),
                    ],
                ]);
            }

            $this->line("Suspension queued for subscription #{$subscription->id}.");
        }
    }

    protected function createRenewalInvoice(Subscription $subscription): Invoice
    {
        $existingInvoice = Invoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', Invoice::STATUS_PENDING)
            ->where('billing_period_start', '<=', now())
            ->where('billing_period_end', '>=', now())
            ->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        $periodStart = $subscription->next_billing_at ?? now();

        $periodEnd = $subscription->billing_cycle === 'yearly'
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'site_id' => $subscription->site_id,
            'invoice_number' => 'REN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'status' => Invoice::STATUS_PENDING,
            'currency' => $subscription->currency ?: 'MYR',
            'amount' => $subscription->amount,
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'due_at' => now(),
        ]);
    }
}
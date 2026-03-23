<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Site;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionStateMachine
{
    public function activate(Subscription $subscription, ?Invoice $invoice = null): Subscription
    {
        $subscription->status = Subscription::STATUS_ACTIVE;
        $subscription->last_paid_at = now();
        $subscription->grace_ends_at = null;
        $subscription->suspended_at = null;

        if ($subscription->billing_cycle === 'yearly') {
            $subscription->next_billing_at = now()->copy()->addYear();
        } else {
            $subscription->next_billing_at = now()->copy()->addMonth();
        }

        $subscription->save();

        if ($invoice) {
            $invoice->status = Invoice::STATUS_PAID;
            $invoice->paid_at = now();
            $invoice->failed_at = null;
            $invoice->failure_reason = null;
            $invoice->save();
        }

        if ($subscription->site) {
            $subscription->site->update([
                'billing_status' => Subscription::STATUS_ACTIVE,
                'status' => Site::STATUS_ACTIVE,
                'suspension_reason' => null,
                'suspended_at' => null,
            ]);
        }

        return $subscription->fresh();
    }

    public function markPastDue(Subscription $subscription, ?Invoice $invoice = null, ?string $reason = null): Subscription
    {
        $subscription->status = Subscription::STATUS_PAST_DUE;
        $subscription->grace_ends_at = now()->addDays(config('services.billing.grace_days', 3));
        $subscription->notes = $reason;
        $subscription->save();

        if ($invoice) {
            $invoice->status = Invoice::STATUS_FAILED;
            $invoice->failed_at = now();
            $invoice->failure_reason = $reason;
            $invoice->save();
        }

        if ($subscription->site) {
            $subscription->site->update([
                'billing_status' => Subscription::STATUS_PAST_DUE,
                'suspension_reason' => $reason,
            ]);
        }

        return $subscription->fresh();
    }
    public function suspend(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->status = Subscription::STATUS_SUSPENDED;
        $subscription->suspended_at = now();
        $subscription->notes = $reason;
        $subscription->save();

        if ($subscription->site) {
            $subscription->site->update([
                'status' => Site::STATUS_SUSPENDED,
                'billing_status' => Subscription::STATUS_SUSPENDED,
                'suspension_reason' => $reason,
                'suspended_at' => now(),
            ]);
        }

        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->status = Subscription::STATUS_CANCELLED;
        $subscription->cancelled_at = now();
        $subscription->save();

        return $subscription->fresh();
    }

    public function expire(Subscription $subscription): Subscription
    {
        $subscription->status = Subscription::STATUS_EXPIRED;
        $subscription->expired_at = now();
        $subscription->save();

        return $subscription->fresh();
    }
}
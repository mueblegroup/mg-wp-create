<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));
        $billingCycle = trim((string) $request->get('billing_cycle'));

        $subscriptions = Subscription::query()
            ->with(['user', 'site', 'plan'])
            ->withCount(['invoices', 'paymentAttempts'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('provider_subscription_id', 'like', "%{$search}%")
                        ->orWhere('provider_customer_reference', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('site', function ($siteQuery) use ($search) {
                            $siteQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('fqdn', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($billingCycle !== '', fn ($query) => $query->where('billing_cycle', $billingCycle))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.subscriptions.index', compact(
            'subscriptions',
            'search',
            'status',
            'billingCycle'
        ));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load([
            'user',
            'site.plan',
            'site.theme',
            'plan',
            'invoices' => fn ($query) => $query->latest(),
            'paymentAttempts' => fn ($query) => $query->latest(),
            'paymentMethods',
        ]);

        return view('superadmin.subscriptions.show', compact('subscription'));
    }

    public function edit(Subscription $subscription): View
    {
        $plans = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('superadmin.subscriptions.edit', compact('subscription', 'plans'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:pending,active,past_due,grace_period,suspended,cancelled,expired'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_cycle' => ['required', 'in:monthly,yearly,annual'],
            'starts_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'last_paid_at' => ['nullable', 'date'],
            'grace_ends_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);

        $subscription->update($validated);

        if ($subscription->site) {
            $subscription->site->update([
                'billing_status' => $validated['status'],
            ]);
        }

        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }
}
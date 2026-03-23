@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Billing</h1>
        <p class="mt-1 text-sm text-gray-600">Manage your subscriptions, invoices, and site billing.</p>
    </div>

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="xl:col-span-2 space-y-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Start Subscription</h2>
                    <p class="mt-1 text-sm text-gray-500">Choose a site and a plan, then continue to HitPay checkout.</p>
                </div>

                <div class="p-6">
                    @if($sites->isEmpty())
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            You do not have any sites yet. Create a site first before starting billing.
                        </div>
                    @elseif($plans->isEmpty())
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            No plans are available yet. Please seed or create your plans first.
                        </div>
                    @else
                        <form method="POST" action="{{ route('billing.checkout') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="site_id" class="block text-sm font-medium text-gray-700 mb-2">Select Site</label>
                                <select
                                    id="site_id"
                                    name="site_id"
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Choose a site</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>
                                            {{ $site->name }} — {{ $site->fqdn ?? $site->subdomain }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('site_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Choose Plan</label>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($plans as $plan)
                                        <label class="relative block cursor-pointer">
                                            <input
                                                type="radio"
                                                name="plan_id"
                                                value="{{ $plan->id }}"
                                                class="peer sr-only"
                                                @checked(old('plan_id') == $plan->id)
                                                required
                                            >

                                            <div class="rounded-2xl border border-gray-200 p-5 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-200 transition">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h3 class="text-base font-semibold text-gray-900">{{ $plan->name }}</h3>
                                                        <p class="mt-1 text-sm text-gray-500">
                                                            {{ $plan->description ?? 'Managed WordPress hosting plan' }}
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <div class="text-lg font-bold text-gray-900">
                                                            RM {{ number_format((float) $plan->price, 2) }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            / {{ $plan->billing_cycle ?? 'monthly' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @error('plan_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                                >
                                    Continue to Payment
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Subscriptions</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Next Billing</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($subscriptions as $subscription)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $subscription->site->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $subscription->plan->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                            {{ $subscription->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ optional($subscription->next_billing_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No subscriptions yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->invoice_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $invoice->subscription?->site?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                            {{ $invoice->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ optional($invoice->due_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No invoices yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900">Billing Notes</h3>
                <ul class="mt-4 space-y-3 text-sm text-gray-600">
                    <li>First payment activates the subscription.</li>
                    <li>Webhook confirmation is the real source of truth.</li>
                    <li>Provisioning should happen only after successful payment.</li>
                    <li>Failed renewals will later move the site into grace period and suspension flow.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
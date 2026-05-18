<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Billing
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Manage your subscriptions, invoices, and site billing.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $paymentRequiredStatuses = [
                    \App\Models\Subscription::STATUS_PENDING,
                    \App\Models\Subscription::STATUS_PAST_DUE,
                    \App\Models\Subscription::STATUS_GRACE_PERIOD,
                    \App\Models\Subscription::STATUS_SUSPENDED,
                ];

                $paymentRequiredSubscriptions = $subscriptions->filter(function ($subscription) use ($paymentRequiredStatuses) {
                    return in_array($subscription->status, $paymentRequiredStatuses, true);
                });
            @endphp

            @if ($paymentRequiredSubscriptions->isNotEmpty())
                <div class="mb-8 rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-yellow-800">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold">
                                Payment Attention Required
                            </h3>
                            <p class="mt-1 text-sm">
                                You have {{ $paymentRequiredSubscriptions->count() }} subscription(s) that need payment.
                                Please complete payment to avoid suspension or reactivate suspended sites.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
                <div class="space-y-8 xl:col-span-2">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Change Package
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Use this page only when you want to upgrade or downgrade a site's package.
                            </p>
                        </div>

                        <div class="p-6">
                            @if ($sites->isEmpty())
                                <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                                    You do not have any sites yet. Create a site first before starting billing.
                                </div>
                            @elseif ($plans->isEmpty())
                                <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                                    No plans are available yet. Please create or seed plans first.
                                </div>
                            @else
                                <form method="POST" action="{{ route('billing.checkout') }}" class="space-y-6">
                                    @csrf

                                    <div>
                                        <label for="site_id" class="mb-2 block text-sm font-medium text-gray-700">
                                            Select Site
                                        </label>
                                        <select
                                            id="site_id"
                                            name="site_id"
                                            class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
                                            <option value="">Choose a site</option>
                                            @foreach ($sites as $site)
                                                <option value="{{ $site->id }}" @selected(old('site_id', $selectedSiteId ?? null) == $site->id)>
                                                    {{ $site->name }} — {{ $site->fqdn ?? $site->subdomain }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('site_id')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-3 block text-sm font-medium text-gray-700">
                                            Choose Plan
                                        </label>

                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            @foreach ($plans as $plan)
                                                <label class="relative block cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        name="plan_id"
                                                        value="{{ $plan->id }}"
                                                        class="peer sr-only"
                                                        @checked(old('plan_id') == $plan->id)
                                                        required
                                                    >

                                                    <div class="rounded-2xl border border-gray-200 p-5 transition peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-200">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div>
                                                                <h4 class="text-base font-semibold text-gray-900">
                                                                    {{ $plan->label ?? $plan->name }}
                                                                </h4>
                                                                <p class="mt-1 text-sm text-gray-500">
                                                                    {{ $plan->description ?? 'Managed WordPress hosting plan' }}
                                                                </p>
                                                            </div>

                                                            <div class="text-right">
                                                                <div class="text-lg font-bold text-gray-900">
                                                                    {{ $plan->currency ?? 'MYR' }} {{ number_format((float) $plan->price, 2) }}
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

                                    <div>
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                        >
                                            Change Package & Pay
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Subscriptions
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Site
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Plan
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Next Billing
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($subscriptions as $subscription)
                                        @php
                                            $requiresPayment = in_array($subscription->status, $paymentRequiredStatuses, true);

                                            $statusClasses = match($subscription->status) {
                                                \App\Models\Subscription::STATUS_ACTIVE => 'bg-green-100 text-green-700',
                                                \App\Models\Subscription::STATUS_PENDING => 'bg-blue-100 text-blue-700',
                                                \App\Models\Subscription::STATUS_PAST_DUE,
                                                \App\Models\Subscription::STATUS_GRACE_PERIOD => 'bg-yellow-100 text-yellow-700',
                                                \App\Models\Subscription::STATUS_SUSPENDED,
                                                \App\Models\Subscription::STATUS_CANCELLED,
                                                \App\Models\Subscription::STATUS_EXPIRED => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp

                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="font-medium">
                                                    {{ $subscription->site->name ?? '—' }}
                                                </div>
                                                <div class="mt-1 break-all text-xs text-gray-500">
                                                    {{ $subscription->site?->fqdn ?? '—' }}
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $subscription->plan->label ?? $subscription->plan->name ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}
                                            </td>

                                            <td class="px-6 py-4 text-sm">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                                    {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                                                </span>

                                                @if ($subscription->grace_ends_at && in_array($subscription->status, [
                                                    \App\Models\Subscription::STATUS_PAST_DUE,
                                                    \App\Models\Subscription::STATUS_GRACE_PERIOD,
                                                ], true))
                                                    <div class="mt-1 text-xs text-yellow-700">
                                                        Grace ends {{ $subscription->grace_ends_at->format('d M Y, h:i A') }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                {{ optional($subscription->next_billing_at)->format('d M Y, h:i A') ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 text-right text-sm">
                                                @if ($requiresPayment)
                                                    <form method="POST" action="{{ route('billing.checkout') }}">
                                                        @csrf
                                                        <input type="hidden" name="site_id" value="{{ $subscription->site_id }}">
                                                        <input type="hidden" name="plan_id" value="{{ $subscription->plan_id }}">

                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                                                            Make Payment
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                                No subscriptions yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Recent Invoices
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Invoice
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Site
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Due
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($invoices as $invoice)
                                        @php
                                            $invoiceRequiresPayment = $invoice->status === \App\Models\Invoice::STATUS_PENDING;

                                            $invoiceStatusClasses = match($invoice->status) {
                                                \App\Models\Invoice::STATUS_PAID => 'bg-green-100 text-green-700',
                                                \App\Models\Invoice::STATUS_PENDING => 'bg-yellow-100 text-yellow-700',
                                                \App\Models\Invoice::STATUS_FAILED,
                                                \App\Models\Invoice::STATUS_VOID => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp

                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $invoice->invoice_number }}
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $invoice->subscription?->site?->name ?? $invoice->site?->name ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}
                                            </td>

                                            <td class="px-6 py-4 text-sm">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $invoiceStatusClasses }}">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                {{ optional($invoice->due_at)->format('d M Y, h:i A') ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 text-right text-sm">
                                                @if ($invoiceRequiresPayment && $invoice->subscription)
                                                    <form method="POST" action="{{ route('billing.invoices.pay', $invoice) }}">
                                                        @csrf
                                                        <input type="hidden" name="site_id" value="{{ $invoice->subscription->site_id }}">
                                                        <input type="hidden" name="plan_id" value="{{ $invoice->subscription->plan_id }}">

                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                                                            Pay Now
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
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
                    @if ($paymentRequiredSubscriptions->isNotEmpty())
                        <div class="overflow-hidden rounded-2xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm">
                            <h3 class="text-base font-semibold text-yellow-900">
                                Overdue / Pending Payments
                            </h3>

                            <div class="mt-4 space-y-3">
                                @foreach ($paymentRequiredSubscriptions as $subscription)
                                    <div class="rounded-xl border border-yellow-200 bg-white p-4">
                                        <div class="font-medium text-gray-900">
                                            {{ $subscription->site?->name ?? 'Site' }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 break-all">
                                            {{ $subscription->site?->fqdn ?? '—' }}
                                        </div>

                                        <div class="mt-2 text-sm text-yellow-800">
                                            {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                                        </div>

                                        @if ($subscription->grace_ends_at)
                                            <div class="mt-1 text-xs text-yellow-700">
                                                Grace ends {{ $subscription->grace_ends_at->format('d M Y, h:i A') }}
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('billing.checkout') }}">
                                            @csrf
                                            <input type="hidden" name="site_id" value="{{ $subscription->site_id }}">
                                            <input type="hidden" name="plan_id" value="{{ $subscription->plan_id }}">

                                            <button type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-lg bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                                                Make Payment
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-gray-900">
                            Billing Notes
                        </h3>

                        <ul class="mt-4 space-y-3 text-sm text-gray-600">
                            <li>First payment activates the subscription.</li>
                            <li>Webhook confirmation is the real source of truth.</li>
                            <li>Provisioning happens only after successful payment.</li>
                            <li>Failed renewals move the site into grace period and suspension flow.</li>
                            <li>Suspended sites can be reactivated after payment succeeds.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
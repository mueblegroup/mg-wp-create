<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription #{{ $subscription->id }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $subscription->provider ?? 'provider' }} · {{ $subscription->provider_subscription_id ?? 'No provider subscription ID' }}
                </p>
            </div>

            <a href="{{ route('superadmin.subscriptions.edit', $subscription) }}"
               class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Edit Subscription
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Status" :value="ucfirst(str_replace('_', ' ', $subscription->status))" />
            <x-superadmin.stat-card title="Plan" :value="$subscription->plan?->label ?? '—'" />
            <x-superadmin.stat-card title="Amount" value="{{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}" />
            <x-superadmin.stat-card title="Cycle" :value="ucfirst($subscription->billing_cycle ?? '—')" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer & Site</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Customer</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->user?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $subscription->user?->email ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Site</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->site?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500 break-all">{{ $subscription->site?->fqdn ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Billing Dates</h2>

                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Starts At</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->starts_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Next Billing</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->next_billing_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Last Paid</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->last_paid_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Grace Ends</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $subscription->grace_ends_at?->format('d M Y, h:i A') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($subscription->notes)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notes</h2>
                <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">{{ $subscription->notes }}</p>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Invoices</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Invoice</th>
                            <th class="py-3 pr-4">Amount</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Due</th>
                            <th class="py-3 pr-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($subscription->invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4 text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ ucfirst($invoice->status) }}</td>
                                <td class="py-3 pr-4 text-gray-500">{{ $invoice->due_at?->format('d M Y') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('superadmin.invoices.show', $invoice) }}" class="text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-gray-500">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.superadmin>
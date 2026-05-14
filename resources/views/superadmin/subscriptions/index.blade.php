<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Subscriptions</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                View and manage customer subscription records.
            </p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:grid-cols-4">
            <input name="search" value="{{ $search ?? '' }}" placeholder="Search user, site, provider ref..."
                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All statuses</option>
                @foreach (['pending', 'active', 'past_due', 'grace_period', 'suspended', 'cancelled', 'expired'] as $item)
                    <option value="{{ $item }}" @selected(($status ?? '') === $item)>
                        {{ ucfirst(str_replace('_', ' ', $item)) }}
                    </option>
                @endforeach
            </select>

            <select name="billing_cycle" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All cycles</option>
                <option value="monthly" @selected(($billingCycle ?? '') === 'monthly')>Monthly</option>
                <option value="yearly" @selected(($billingCycle ?? '') === 'yearly')>Yearly</option>
                <option value="annual" @selected(($billingCycle ?? '') === 'annual')>Annual</option>
            </select>

            <button class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Filter
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Cycle</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Next Billing</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($subscriptions as $subscription)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $subscription->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $subscription->user?->email ?? '—' }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-gray-900 dark:text-white">{{ $subscription->site?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $subscription->site?->fqdn ?? '—' }}</div>
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $subscription->plan?->label ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ ucfirst($subscription->billing_cycle ?? '—') }}
                                </td>

                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                        {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-gray-500">
                                    {{ $subscription->next_billing_at ? $subscription->next_billing_at->format('d M Y') : '—' }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.subscriptions.show', $subscription) }}" class="text-sm font-medium text-blue-600 hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">No subscriptions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $subscriptions->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
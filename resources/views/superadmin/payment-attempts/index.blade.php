<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transactions</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">View payment attempts and HitPay event payloads.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-3">
            <input name="search" value="{{ $search ?? '' }}" placeholder="Search charge, reference, user..."
                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All statuses</option>
                <option value="succeeded" @selected(($status ?? '') === 'succeeded')>Succeeded</option>
                <option value="failed" @selected(($status ?? '') === 'failed')>Failed</option>
                <option value="pending" @selected(($status ?? '') === 'pending')>Pending</option>
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
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Attempted</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($paymentAttempts as $attempt)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $attempt->provider_event_type ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $attempt->provider_charge_id ?? $attempt->provider_reference ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attempt->subscription?->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attempt->subscription?->site?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $attempt->currency }} {{ number_format((float) $attempt->amount, 2) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ ucfirst($attempt->status ?? '—') }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $attempt->attempted_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.payment-attempts.show', $attempt) }}" class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $paymentAttempts->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
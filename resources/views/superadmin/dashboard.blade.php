<x-layouts.superadmin>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Super Admin Dashboard
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Platform overview, subscriptions, provisioning status, and recent activity.
                    </p>
                </div>

                <div class="rounded-full bg-black px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white dark:bg-white dark:text-black">
                    Superadmin
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-superadmin.stat-card title="Total Users" :value="$stats['total_users']" />
                <x-superadmin.stat-card title="Total Sites" :value="$stats['total_sites']" />
                <x-superadmin.stat-card title="Active Sites" :value="$stats['active_sites']" />
                <x-superadmin.stat-card title="Failed Sites" :value="$stats['failed_sites']" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-superadmin.stat-card title="Provisioning" :value="$stats['provisioning_sites']" />
                <x-superadmin.stat-card title="Suspended Sites" :value="$stats['suspended_sites']" />
                <x-superadmin.stat-card title="Active Subscriptions" :value="$stats['active_subscriptions']" />
                <x-superadmin.stat-card title="Past Due" :value="$stats['past_due_subscriptions']" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-superadmin.stat-card title="Monthly Revenue" value="RM {{ number_format($stats['mrr'], 2) }}" />
                <x-superadmin.stat-card title="Annual Revenue" value="RM {{ number_format($stats['arr'], 2) }}" />
                <x-superadmin.stat-card title="Pending Invoices" :value="$stats['pending_invoices']" />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sites</h2>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="py-3 pr-4">Site</th>
                                    <th class="py-3 pr-4">User</th>
                                    <th class="py-3 pr-4">Plan</th>
                                    <th class="py-3 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($recentSites as $site)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $site->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 break-all">
                                                {{ $site->fqdn }}
                                            </div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                                            {{ $site->user?->name ?? '—' }}
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                                            {{ $site->plan?->label ?? '—' }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst(str_replace('_', ' ', $site->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-gray-500">No sites yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Users</h2>

                    <div class="mt-4 space-y-3">
                        @forelse ($recentUsers as $user)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 p-3 dark:border-gray-700">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $user->created_at?->format('d M Y') }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">No users yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h2>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="py-3 pr-4">Invoice</th>
                                    <th class="py-3 pr-4">User</th>
                                    <th class="py-3 pr-4">Amount</th>
                                    <th class="py-3 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td class="py-3 pr-4 text-gray-900 dark:text-white">
                                            {{ $invoice->invoice_number }}
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                                            {{ $invoice->user?->name ?? '—' }}
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                                            {{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-gray-500">No invoices yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-900/40 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Failed Provisioning</h2>

                    <div class="mt-4 space-y-3">
                        @forelse ($failedSites as $site)
                            <div class="rounded-xl border border-red-100 bg-red-50 p-3 dark:border-red-900/40 dark:bg-red-900/20">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $site->name }}
                                </div>
                                <div class="text-xs text-gray-500 break-all">
                                    {{ $site->fqdn }}
                                </div>
                                <div class="mt-2 text-xs text-red-700 dark:text-red-300 break-words">
                                    {{ $site->provisioning_error ?: 'No error message stored.' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">No failed sites.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.superadmin>
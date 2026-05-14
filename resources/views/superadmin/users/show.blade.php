<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>

            <a href="{{ route('superadmin.users.edit', $user) }}" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Edit User
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card title="Role" :value="ucfirst($user->role)" />
            <x-admin.stat-card title="Sites" :value="$user->sites->count()" />
            <x-admin.stat-card title="Subscriptions" :value="$user->subscriptions->count()" />
            <x-admin.stat-card title="Invoices" :value="$user->invoices->count()" />
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sites</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Site</th>
                            <th class="py-3 pr-4">Plan</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Billing</th>
                            <th class="py-3 pr-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($user->sites as $site)
                            <tr>
                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $site->name }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $site->fqdn }}</div>
                                </td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $site->plan?->label ?? '—' }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $site->status)) }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $site->billing_status ?? '—')) }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('superadmin.sites.show', $site) }}" class="text-sm font-medium text-blue-600 hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-gray-500">No sites found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Invoice</th>
                            <th class="py-3 pr-4">Site</th>
                            <th class="py-3 pr-4">Amount</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($user->invoices->take(10) as $invoice)
                            <tr>
                                <td class="py-3 pr-4 text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $invoice->site?->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ ucfirst($invoice->status) }}</td>
                                <td class="py-3 pr-4 text-gray-500">{{ $invoice->created_at?->format('d M Y') }}</td>
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
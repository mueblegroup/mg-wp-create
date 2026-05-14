<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sites</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                View and manage all provisioned and pending WordPress sites.
            </p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:grid-cols-4">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search site, domain, user..."
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All site statuses</option>
                @foreach (['pending_payment', 'provisioning', 'active', 'suspended', 'failed'] as $item)
                    <option value="{{ $item }}" @selected($status === $item)>
                        {{ ucfirst(str_replace('_', ' ', $item)) }}
                    </option>
                @endforeach
            </select>

            <select name="billing_status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All billing statuses</option>
                @foreach (['pending', 'active', 'past_due', 'grace_period', 'suspended', 'cancelled', 'expired'] as $item)
                    <option value="{{ $item }}" @selected($billingStatus === $item)>
                        {{ ucfirst(str_replace('_', ' ', $item)) }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Filter
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Site Status</th>
                            <th class="px-4 py-3">Billing</th>
                            <th class="px-4 py-3">Provisioned</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($sites as $site)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $site->name }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $site->fqdn }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 dark:text-white">{{ $site->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $site->user?->email ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $site->plan?->label ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $site->status)) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $site->billing_status ?? '—')) }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $site->provisioned_at ? $site->provisioned_at->format('d M Y') : 'Pending' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.sites.show', $site) }}" class="text-sm font-medium text-blue-600 hover:underline">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                    No sites found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $sites->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
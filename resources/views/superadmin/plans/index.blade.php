<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Plans</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage Bronze, Silver, Gold and future package pricing.</p>
            </div>

            <a href="{{ route('superadmin.plans.create') }}" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Create Plan
            </a>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-3">
            <input name="search" value="{{ $search }}" placeholder="Search plans..."
                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
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
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Custom Domain</th>
                            <th class="px-4 py-3">Max Themes</th>
                            <th class="px-4 py-3">Sites</th>
                            <th class="px-4 py-3">Subscriptions</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($plans as $plan)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $plan->label }}</div>
                                    <div class="text-xs text-gray-500">{{ $plan->name }} · Level {{ $plan->level }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $plan->currency }} {{ number_format($plan->price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $plan->allows_custom_domain ? 'Yes' : 'No' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $plan->max_themes }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $plan->sites_count }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $plan->subscriptions_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.plans.show', $plan) }}" class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">No plans found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $plans->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
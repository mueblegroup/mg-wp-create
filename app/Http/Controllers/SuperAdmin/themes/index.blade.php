<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Themes</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage WordPress theme packages and plan restrictions.</p>
            </div>

            <a href="{{ route('superadmin.themes.create') }}" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                Create Theme
            </a>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:grid-cols-4">
            <input name="search" value="{{ $search }}" placeholder="Search themes..."
                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>

            <select name="min_plan_level" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All levels</option>
                <option value="1" @selected($level === '1')>Bronze / Level 1</option>
                <option value="2" @selected($level === '2')>Silver / Level 2</option>
                <option value="3" @selected($level === '3')>Gold / Level 3</option>
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
                            <th class="px-4 py-3">Theme</th>
                            <th class="px-4 py-3">Min Plan</th>
                            <th class="px-4 py-3">ZIP</th>
                            <th class="px-4 py-3">Sites</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($themes as $theme)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $theme->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $theme->slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">Level {{ $theme->min_plan_level }}</td>
                                <td class="px-4 py-3">
                                    <span class="{{ $theme->zip_exists ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $theme->zip_exists ? 'Found' : 'Missing' }}
                                    </span>
                                    <div class="text-xs text-gray-500 break-all">{{ $theme->zip_path }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $theme->sites_count }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $theme->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.themes.show', $theme) }}" class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No themes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $themes->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
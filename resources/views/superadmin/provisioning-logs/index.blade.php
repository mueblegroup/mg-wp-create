<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Provisioning Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Track Hestia, WordPress, SSL, database, and provisioning activity.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-3">
            <input name="search" value="{{ $search ?? '' }}" placeholder="Search action, message, site..."
                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">

            <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                <option value="">All statuses</option>
                <option value="info" @selected(($status ?? '') === 'info')>Info</option>
                <option value="success" @selected(($status ?? '') === 'success')>Success</option>
                <option value="error" @selected(($status ?? '') === 'error')>Error</option>
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
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Message</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($provisioningLogs as $log)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $log->action }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 dark:text-white">{{ $log->site?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $log->site?->fqdn ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ ucfirst($log->status) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('superadmin.provisioning-logs.show', $log) }}" class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No provisioning logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $provisioningLogs->links() }}
            </div>
        </div>
    </div>
</x-layouts.superadmin>
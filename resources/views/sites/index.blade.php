<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Sites</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage your provisioned WordPress sites.</p>
                </div>

                <a href="{{ route('sites.create') }}"
                   class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                    Create Site
                </a>
            </div>

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Theme</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">WordPress</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($sites as $site)
                                @php
                                    $statusClasses = match($site->status) {
                                        'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'provisioning' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'suspended' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                                    };
                                @endphp

                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $site->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $site->fqdn }}</div>

                                        @if ($site->provisioning_error)
                                            <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                                                {{ \Illuminate\Support\Str::limit($site->provisioning_error, 100) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $site->plan?->label }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $site->theme?->name ?? 'No Theme' }}</td>                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                            {{ ucfirst(str_replace('_', ' ', $site->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        @if ($site->wordpress_admin_url)
                                            <a href="{{ $site->wordpress_admin_url }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                                Open Admin
                                            </a>
                                        @else
                                            Pending
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('sites.show', $site) }}"
                                               class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                                View
                                            </a>

                                            @if ($site->status !== 'provisioning')
                                                <form method="POST"
                                                      action="{{ route('sites.destroy', $site) }}"
                                                      onsubmit="return confirm('Are you sure you want to delete this site? This will remove the hosted site and related records.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No sites found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $sites->links() }}

        </div>
    </div>
</x-app-layout>
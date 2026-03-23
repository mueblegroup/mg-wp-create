<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Overview of your managed WordPress sites.</p>
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

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Sites</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $sitesCount }}</div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Active Sites</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $activeSitesCount }}</div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Suspended Sites</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $suspendedSitesCount }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sites</h2>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($sites as $site)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $site->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $site->fqdn }}</div>
                                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                {{ $site->plan?->label }} • {{ $site->theme?->name ?? 'No Theme' }}                                </div>

                                @if ($site->provisioning_error)
                                    <div class="mt-2 text-xs text-red-600 dark:text-red-400">
                                        {{ \Illuminate\Support\Str::limit($site->provisioning_error, 120) }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                @php
                                    $statusClasses = match($site->status) {
                                        'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'provisioning' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'suspended' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                                    };
                                @endphp

                                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $site->status)) }}
                                </span>

                                <a href="{{ route('sites.show', $site) }}"
                                   class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                    View
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">
                            No sites yet. Create your first site to get started.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
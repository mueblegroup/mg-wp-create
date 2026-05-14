<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $site->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 break-all">{{ $site->fqdn }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('superadmin.sites.edit', $site) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    Edit Site
                </a>

                <form method="POST" action="{{ route('superadmin.sites.retry-provisioning', $site) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                        Retry Provisioning
                    </button>
                </form>

                @if ($site->status !== \App\Models\Site::STATUS_SUSPENDED)
                    <form method="POST" action="{{ route('superadmin.sites.suspend', $site) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-yellow-300 px-4 py-2 text-sm font-medium text-yellow-700 hover:bg-yellow-50">
                            Suspend
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('superadmin.sites.unsuspend', $site) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-green-300 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                            Unsuspend
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($site->provisioning_error)
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700">
                <div class="font-semibold">Provisioning Error</div>
                <div class="mt-2 text-sm break-words">{{ $site->provisioning_error }}</div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Site Status" :value="ucfirst(str_replace('_', ' ', $site->status))" />
            <x-superadmin.stat-card title="Billing Status" :value="ucfirst(str_replace('_', ' ', $site->billing_status ?? '—'))" />
            <x-superadmin.stat-card title="Plan" :value="$site->plan?->label ?? '—'" />
            <x-superadmin.stat-card title="Theme" :value="$site->theme?->name ?? 'No Theme'" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Owner</h2>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Name</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $site->user?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Email</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $site->user?->email ?? '—' }}</span>
                    </div>
                    @if ($site->user)
                        <div class="pt-3">
                            <a href="{{ route('superadmin.users.show', $site->user) }}" class="text-sm font-medium text-blue-600 hover:underline">
                                View Owner
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">WordPress Access</h2>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Admin URL</span>
                        <span class="font-medium text-gray-900 dark:text-white break-all">{{ $site->wordpress_admin_url ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Username</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $site->wordpress_admin_username ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Email</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $site->wordpress_admin_email ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Provisioning Logs</h2>

            <div class="mt-4 space-y-4">
                @forelse ($site->provisioningLogs as $log)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $log->action }}</div>
                            <div class="text-xs text-gray-500">{{ $log->created_at?->format('d M Y, h:i A') }}</div>
                        </div>

                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $log->message }}
                        </div>

                        <div class="mt-2 text-xs uppercase tracking-wider text-gray-500">
                            {{ $log->status }}
                        </div>

                        @if (! empty($log->context))
                            <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-3 text-xs text-gray-100">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No provisioning logs.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.superadmin>
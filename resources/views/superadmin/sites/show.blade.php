<x-layouts.superadmin>
    <div class="max-w-full space-y-6 overflow-hidden">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <h1 class="break-words text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $site->name }}
                </h1>
                <p class="break-all text-sm text-gray-500 dark:text-gray-400">
                    {{ $site->fqdn }}
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ route('superadmin.sites.edit', $site) }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    Edit Site
                </a>

                <form method="POST" action="{{ route('superadmin.sites.retry-provisioning', $site) }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                        Retry Provisioning
                    </button>
                </form>

                @if ($site->status !== \App\Models\Site::STATUS_SUSPENDED)
                    <form method="POST" action="{{ route('superadmin.sites.suspend', $site) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-yellow-300 px-4 py-2 text-sm font-medium text-yellow-700 hover:bg-yellow-50">
                            Suspend
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('superadmin.sites.unsuspend', $site) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border border-green-300 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                            Unsuspend
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($site->provisioning_error)
            <div class="max-w-full rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700">
                <div class="font-semibold">Provisioning Error</div>
                <div class="mt-2 break-words text-sm">
                    {{ $site->provisioning_error }}
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-superadmin.stat-card title="Site Status" :value="ucfirst(str_replace('_', ' ', $site->status))" />
            <x-superadmin.stat-card title="Billing Status" :value="ucfirst(str_replace('_', ' ', $site->billing_status ?? '—'))" />
            <x-superadmin.stat-card title="Plan" :value="$site->plan?->label ?? '—'" />
            <x-superadmin.stat-card title="Theme" :value="$site->theme?->name ?? 'No Theme'" />
        </div>

        <div class="grid min-w-0 gap-6 xl:grid-cols-2">
            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Owner</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="grid gap-1 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <span class="text-gray-500">Name</span>
                        <span class="min-w-0 break-words font-medium text-gray-900 dark:text-white">
                            {{ $site->user?->name ?? '—' }}
                        </span>
                    </div>

                    <div class="grid gap-1 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <span class="text-gray-500">Email</span>
                        <span class="min-w-0 break-all font-medium text-gray-900 dark:text-white">
                            {{ $site->user?->email ?? '—' }}
                        </span>
                    </div>

                    @if ($site->user)
                        <div class="pt-3">
                            <a href="{{ route('superadmin.users.show', $site->user) }}"
                               class="text-sm font-medium text-blue-600 hover:underline">
                                View Owner
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">WordPress Access</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="grid gap-1 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <span class="text-gray-500">Admin URL</span>
                        <span class="min-w-0 break-all font-medium text-gray-900 dark:text-white">
                            {{ $site->wordpress_admin_url ?? '—' }}
                        </span>
                    </div>

                    <div class="grid gap-1 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <span class="text-gray-500">Username</span>
                        <span class="min-w-0 break-words font-medium text-gray-900 dark:text-white">
                            {{ $site->wordpress_admin_username ?? '—' }}
                        </span>
                    </div>

                    <div class="grid gap-1 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <span class="text-gray-500">Email</span>
                        <span class="min-w-0 break-all font-medium text-gray-900 dark:text-white">
                            {{ $site->wordpress_admin_email ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Provisioning Logs</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Latest provisioning events for this site.
                    </p>
                </div>

                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ $site->provisioningLogs->count() }} logs
                </span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($site->provisioningLogs as $log)
                    @php
                        $statusClasses = match ($log->status) {
                            'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            'error' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        };

                        $shortMessage = \Illuminate\Support\Str::limit($log->message ?: 'No message stored.', 120);
                    @endphp

                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($log->status) }}
                                    </span>

                                    <span class="break-words text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </div>

                                <p class="mt-2 break-words text-sm text-gray-600 dark:text-gray-300">
                                    {{ $shortMessage }}
                                </p>
                            </div>

                            <div class="shrink-0 text-xs text-gray-500">
                                {{ $log->created_at?->format('d M Y, h:i A') }}
                            </div>
                        </div>

                        @if (! empty($log->context) || strlen((string) $log->message) > 120)
                            <details class="mt-3 group">
                                <summary class="cursor-pointer select-none text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    View details
                                </summary>

                                <div class="mt-3 space-y-3">
                                    @if (strlen((string) $log->message) > 120)
                                        <div class="rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-950 dark:text-gray-300">
                                            <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Full Message
                                            </div>
                                            <div class="break-words">
                                                {{ $log->message }}
                                            </div>
                                        </div>
                                    @endif

                                    @if (! empty($log->context))
                                        <div class="rounded-lg bg-gray-950 p-3">
                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                                Context
                                            </div>

                                            <pre class="max-h-72 overflow-y-auto whitespace-pre-wrap break-words text-xs text-gray-100">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                        No provisioning logs.
                    </div>
                @endforelse
            </div>
        </div>
            </div>
        </x-layouts.superadmin>
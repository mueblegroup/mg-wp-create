<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $site->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $site->fqdn }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('sites.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Back
                    </a>

                    @if (in_array($site->status, ['failed', 'pending_payment']))
                        <form method="POST" action="{{ route('sites.retry-provisioning', $site) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                Retry Provisioning
                            </button>
                        </form>
                    @endif
                </div>
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

            @if ($site->provisioning_error)
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 dark:border-red-900/40 dark:bg-red-900/20">
                    <div class="text-sm font-semibold text-red-700 dark:text-red-400">Provisioning Error</div>
                    <div class="mt-2 text-sm text-red-600 dark:text-red-300 break-words">
                        {{ $site->provisioning_error }}
                    </div>
                </div>
            @endif

            @php
                $statusClasses = match($site->status) {
                    'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'provisioning' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'suspended' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                };
            @endphp

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Site Details</h2>

                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                {{ ucfirst(str_replace('_', ' ', $site->status)) }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Plan</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $site->plan?->label }}</div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Theme</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $site->theme?->name ?? 'No Theme' }}</div>                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Provisioned At</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->provisioned_at ? $site->provisioned_at->format('d M Y, h:i A') : 'Pending' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Primary Domain</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white break-all">
                                    {{ $site->primary_domain ?: $site->fqdn }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Hestia Username</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->hestia_username ?: 'Pending' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Hestia Domain</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white break-all">
                                    {{ $site->hestia_domain ?: 'Pending' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">WordPress Admin Username</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->wordpress_admin_username ?: 'Pending' }}
                                </div>
                            </div>
                            <div>
    <div class="text-xs uppercase tracking-wider text-gray-500">WordPress Admin Password</div>
    <div class="mt-1 flex items-center gap-2">
            <input
                id="wp-admin-password"
                type="text"
                readonly
                value="{{ $site->wp_admin_password ?: 'Pending' }}"
                class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 shadow-sm focus:border-black focus:ring-black dark:border-gray-600 dark:bg-gray-900 dark:text-white"
            >
            @if($site->wp_admin_password)
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText(document.getElementById('wp-admin-password').value)"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Copy
                </button>
            @endif
        </div>
    </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">WordPress Admin Email</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white break-all">
                                    {{ $site->wordpress_admin_email ?: 'Pending' }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="text-xs uppercase tracking-wider text-gray-500">WordPress Admin URL</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white break-all">
                                    @if ($site->wordpress_admin_url)
                                        <a href="{{ $site->wordpress_admin_url }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $site->wordpress_admin_url }}
                                        </a>
                                    @else
                                        Pending
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Provisioning Logs</h2>

                        <div class="mt-4 space-y-4">
                            @forelse ($site->provisioningLogs as $log)
                                @php
                                    $logClasses = match($log->status) {
                                        'success' => 'border-green-200 bg-green-50 dark:border-green-900/40 dark:bg-green-900/20',
                                        'error' => 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/20',
                                        default => 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/30',
                                    };
                                @endphp

                                <div class="rounded-xl border p-4 {{ $logClasses }}">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $log->action }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->created_at?->format('d M Y, h:i A') }}
                                        </div>
                                    </div>

                                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $log->message }}
                                    </div>

                                    <div class="mt-2 text-xs uppercase tracking-wider text-gray-400">
                                        {{ $log->status }}
                                    </div>

                                    @if (!empty($log->context))
                                        <div class="mt-3 rounded-lg bg-gray-900 p-3 text-xs text-gray-100 overflow-x-auto dark:bg-black">
<pre>{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No logs yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Subscription</h2>

                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Provider</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $site->subscription?->provider ?? '—' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    @if ($site->subscription)
                                        {{ $site->subscription->currency }} {{ number_format($site->subscription->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Status</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $site->subscription ? ucfirst(str_replace('_', ' ', $site->subscription->status)) : '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Renews At</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $site->subscription?->renews_at ? $site->subscription->renews_at->format('d M Y, h:i A') : '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Domains</h2>

                        <div class="mt-4 space-y-3">
                            @foreach ($site->domains as $domain)
                                <div class="rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <div class="font-medium text-gray-900 dark:text-white break-all">{{ $domain->domain }}</div>
                                    <div class="mt-1 text-gray-500 dark:text-gray-400">
                                        {{ $domain->is_primary ? 'Primary' : 'Secondary' }} •
                                        {{ $domain->is_verified ? 'Verified' : 'Pending' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>

                        <div class="mt-4 space-y-3">
                            @if ($site->wordpress_admin_url)
                                <a href="{{ $site->wordpress_admin_url }}" target="_blank"
                                   class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                    Open WordPress Admin
                                </a>
                            @endif

                            @if (in_array($site->status, ['failed', 'pending_payment']))
                                <form method="POST" action="{{ route('sites.retry-provisioning', $site) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Retry Provisioning
                                    </button>
                                </form>
                            @endif

                            @if ($site->status !== 'provisioning')
                                <form method="POST"
                                      action="{{ route('sites.destroy', $site) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this site? This action removes the hosted site and related records.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">
                                        Delete Site
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $subscription = $site->subscription;

                $pendingInvoice = $subscription?->invoices
                    ? $subscription->invoices
                        ->where('status', \App\Models\Invoice::STATUS_PENDING)
                        ->sortByDesc('created_at')
                        ->first()
                    : null;

                $paymentRequiredStatuses = [
                    \App\Models\Subscription::STATUS_PENDING,
                    \App\Models\Subscription::STATUS_PAST_DUE,
                    \App\Models\Subscription::STATUS_GRACE_PERIOD,
                    \App\Models\Subscription::STATUS_SUSPENDED,
                ];

                $requiresPayment = $subscription && in_array($subscription->status, $paymentRequiredStatuses, true);

                $statusClasses = match($site->status) {
                    'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'provisioning' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'suspended' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                };

                $subscriptionStatusClasses = match($subscription?->status) {
                    \App\Models\Subscription::STATUS_ACTIVE => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    \App\Models\Subscription::STATUS_PENDING => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    \App\Models\Subscription::STATUS_PAST_DUE,
                    \App\Models\Subscription::STATUS_GRACE_PERIOD => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    \App\Models\Subscription::STATUS_SUSPENDED,
                    \App\Models\Subscription::STATUS_CANCELLED,
                    \App\Models\Subscription::STATUS_EXPIRED => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                };
            @endphp

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="break-words text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $site->name }}
                    </h1>
                    <p class="break-all text-sm text-gray-500 dark:text-gray-400">
                        {{ $site->fqdn }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('sites.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Back
                    </a>

                    @if ($requiresPayment)
                        @if ($pendingInvoice)
                            <form method="POST" action="{{ route('billing.invoices.pay', $pendingInvoice) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                    Make Payment
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('billing.sites.pay', $site) }}">
                                @csrf

                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200 sm:w-auto">
                                    Make Payment
                                </button>
                            </form>
                        @endif
                    @endif

                    @if (in_array($site->status, ['failed', 'pending_payment'], true))
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

            @if ($requiresPayment)
                <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-yellow-800 dark:border-yellow-900/40 dark:bg-yellow-900/20 dark:text-yellow-200">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-semibold">
                                Payment Required
                            </div>

                            <div class="mt-1 text-sm">
                                @if ($subscription->status === \App\Models\Subscription::STATUS_PAST_DUE)
                                    Your subscription renewal payment is due.
                                    @if ($subscription->grace_ends_at)
                                        Please pay before {{ $subscription->grace_ends_at->format('d M Y, h:i A') }} to avoid suspension.
                                    @endif
                                @elseif ($subscription->status === \App\Models\Subscription::STATUS_SUSPENDED)
                                    This site has been suspended because payment is overdue. Make payment to reactivate it.
                                @elseif ($subscription->status === \App\Models\Subscription::STATUS_PENDING)
                                    Your subscription is pending payment. Complete payment to activate this site.
                                @elseif ($subscription->status === \App\Models\Subscription::STATUS_GRACE_PERIOD)
                                    Your subscription is in grace period. Please make payment to avoid suspension.
                                    @if ($subscription->grace_ends_at)
                                        Grace period ends on {{ $subscription->grace_ends_at->format('d M Y, h:i A') }}.
                                    @endif
                                @else
                                    Your subscription needs attention. Please make payment to continue service.
                                @endif

                                @if (! $pendingInvoice)
                                    <div class="mt-2 text-xs">
                                        No pending invoice was found yet. Please open Billing or run the overdue billing checker.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if ($pendingInvoice)
                                <form method="POST" action="{{ route('billing.invoices.pay', $pendingInvoice) }}">
                                    @csrf

                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200 sm:w-auto">
                                        Make Payment
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('billing.sites.pay', $site) }}">
                                    @csrf

                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200 sm:w-auto">
                                        Make Payment
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($site->provisioning_error)
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 dark:border-red-900/40 dark:bg-red-900/20">
                    <div class="text-sm font-semibold text-red-700 dark:text-red-400">
                        Provisioning Error
                    </div>
                    <div class="mt-2 break-words text-sm text-red-600 dark:text-red-300">
                        {{ $site->provisioning_error }}
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Site Details
                            </h2>

                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                {{ ucfirst(str_replace('_', ' ', $site->status)) }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Plan</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->plan?->label ?? '—' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Theme</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->theme?->name ?? 'No Theme' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Provisioned At</div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->provisioned_at ? $site->provisioned_at->format('d M Y, h:i A') : 'Pending' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-wider text-gray-500">Primary Domain</div>
                                <div class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-white">
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
                                <div class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-white">
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

                                    @if ($site->wp_admin_password)
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
                                <div class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->wordpress_admin_email ?: 'Pending' }}
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="text-xs uppercase tracking-wider text-gray-500">WordPress Admin Access</div>

                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    @if ($site->wordpress_admin_url)
                                        @if ($site->status === \App\Models\Site::STATUS_ACTIVE && $site->wordpress_sso_secret)
                                            <a href="{{ route('sites.wp-admin-login', $site) }}"
                                               target="_blank"
                                               class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                                SSO Login
                                            </a>
                                        @endif

                                        <a href="{{ $site->wordpress_admin_url }}"
                                           target="_blank"
                                           class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Manual Login
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Pending</span>
                                    @endif
                                </div>

                                @if ($site->wordpress_admin_url)
                                    <div class="mt-2 break-all text-xs text-gray-500 dark:text-gray-400">
                                        {{ $site->wordpress_admin_url }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Provisioning Logs
                                </h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Latest provisioning and billing events for this site.
                                </p>
                            </div>

                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $site->provisioningLogs->count() }} logs
                            </span>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($site->provisioningLogs as $log)
                                @php
                                    $logClasses = match($log->status) {
                                        'success' => 'border-green-200 bg-green-50 dark:border-green-900/40 dark:bg-green-900/20',
                                        'error' => 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/20',
                                        default => 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/30',
                                    };

                                    $logBadgeClasses = match($log->status) {
                                        'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'error' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    };

                                    $shortMessage = \Illuminate\Support\Str::limit($log->message ?: 'No message stored.', 120);
                                @endphp

                                <div class="rounded-xl border p-4 {{ $logClasses }}">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $logBadgeClasses }}">
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

                                        <div class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->created_at?->format('d M Y, h:i A') }}
                                        </div>
                                    </div>

                                    @if (! empty($log->context) || strlen((string) $log->message) > 120)
                                        <details class="group mt-3">
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
                                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    No provisioning logs.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Subscription
                            </h2>

                            @if ($subscription)
                                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $subscriptionStatusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Provider</span>
                                <span class="break-all text-right font-medium text-gray-900 dark:text-white">
                                    {{ $subscription?->provider ?? '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    @if ($subscription)
                                        {{ $subscription->currency }} {{ number_format((float) $subscription->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Billing Cycle</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $subscription?->billing_cycle ? ucfirst($subscription->billing_cycle) : '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Status</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $subscription ? ucfirst(str_replace('_', ' ', $subscription->status)) : '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-gray-500 dark:text-gray-400">Renews At</span>
                                <span class="text-right font-medium text-gray-900 dark:text-white">
                                    {{ $subscription?->next_billing_at ? $subscription->next_billing_at->format('d M Y, h:i A') : '—' }}
                                </span>
                            </div>

                            @if ($subscription?->grace_ends_at)
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-gray-500 dark:text-gray-400">Grace Ends</span>
                                    <span class="text-right font-medium text-gray-900 dark:text-white">
                                        {{ $subscription->grace_ends_at->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                            @endif

                            @if ($pendingInvoice)
                                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-yellow-800 dark:border-yellow-900/40 dark:bg-yellow-900/20 dark:text-yellow-200">
                                    <div class="text-xs font-semibold uppercase tracking-wider">
                                        Pending Invoice
                                    </div>
                                    <div class="mt-1 text-sm font-medium">
                                        {{ $pendingInvoice->invoice_number }}
                                    </div>
                                    <div class="mt-1 text-xs">
                                        {{ $pendingInvoice->currency }} {{ number_format((float) $pendingInvoice->amount, 2) }}
                                        @if ($pendingInvoice->due_at)
                                            • Due {{ $pendingInvoice->due_at->format('d M Y, h:i A') }}
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($requiresPayment)
                                <div class="pt-3">
                                    @if ($pendingInvoice)
                                        <form method="POST" action="{{ route('billing.invoices.pay', $pendingInvoice) }}">
                                            @csrf

                                            <button type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                                Make Payment
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('billing.sites.pay', $site) }}">
                                            @csrf

                                            <button type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                                Make Payment
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Domains
                        </h2>

                        <div class="mt-4 space-y-3">
                            @forelse ($site->domains as $domain)
                                <div class="rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <div class="break-all font-medium text-gray-900 dark:text-white">
                                        {{ $domain->domain }}
                                    </div>
                                    <div class="mt-1 text-gray-500 dark:text-gray-400">
                                        {{ $domain->is_primary ? 'Primary' : 'Secondary' }} •
                                        {{ $domain->is_verified ? 'Verified' : 'Pending' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    No domains found.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Quick Actions
                        </h2>

                        <div class="mt-4 space-y-3">
                            @if ($requiresPayment)
                                @if ($pendingInvoice)
                                    <form method="POST" action="{{ route('billing.invoices.pay', $pendingInvoice) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                            Make Payment
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('billing.sites.pay', $site) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                            Make Payment
                                        </button>
                                    </form>
                                @endif
                            @endif

                            @if ($site->wordpress_admin_url)
                                @if ($site->status === \App\Models\Site::STATUS_ACTIVE && $site->wordpress_sso_secret)
                                    <a href="{{ route('sites.wp-admin-login', $site) }}"
                                       target="_blank"
                                       class="inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                        SSO Login to WordPress
                                    </a>
                                @endif

                                <a href="{{ $site->wordpress_admin_url }}"
                                   target="_blank"
                                   class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Manual WordPress Login
                                </a>
                            @endif

                            @if (in_array($site->status, ['failed', 'pending_payment'], true))
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

                            <a href="{{ route('billing.index', ['site_id' => $site->id]) }}"
                               class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                Change Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
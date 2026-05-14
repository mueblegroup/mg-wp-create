<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transaction #{{ $paymentAttempt->id }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $paymentAttempt->provider_event_type ?? 'Payment attempt' }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Status" :value="ucfirst($paymentAttempt->status ?? '—')" />
            <x-superadmin.stat-card title="Amount" value="{{ $paymentAttempt->currency }} {{ number_format((float) $paymentAttempt->amount, 2) }}" />
            <x-superadmin.stat-card title="Attempted" :value="$paymentAttempt->attempted_at?->format('d M Y') ?? '—'" />
            <x-superadmin.stat-card title="Succeeded" :value="$paymentAttempt->succeeded_at?->format('d M Y') ?? '—'" />
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Provider Details</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Charge ID</div>
                    <div class="mt-1 break-all text-gray-900 dark:text-white">{{ $paymentAttempt->provider_charge_id ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Reference</div>
                    <div class="mt-1 break-all text-gray-900 dark:text-white">{{ $paymentAttempt->provider_reference ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Failure Reason</div>
                    <div class="mt-1 text-gray-900 dark:text-white">{{ $paymentAttempt->failure_reason ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Invoice</div>
                    <div class="mt-1 text-gray-900 dark:text-white">
                        @if ($paymentAttempt->invoice)
                            <a href="{{ route('superadmin.invoices.show', $paymentAttempt->invoice) }}" class="text-blue-600 hover:underline">
                                {{ $paymentAttempt->invoice->invoice_number }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($paymentAttempt->payload))
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Raw Payload</h2>
                <pre class="mt-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($paymentAttempt->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
</x-layouts.superadmin>
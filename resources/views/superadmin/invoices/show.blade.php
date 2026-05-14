<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Invoice #{{ $invoice->id }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Status" :value="ucfirst($invoice->status)" />
            <x-superadmin.stat-card title="Amount" value="{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}" />
            <x-superadmin.stat-card title="Due At" :value="$invoice->due_at?->format('d M Y') ?? '—'" />
            <x-superadmin.stat-card title="Paid At" :value="$invoice->paid_at?->format('d M Y') ?? '—'" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer & Site</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Customer</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $invoice->user?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $invoice->user?->email ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Site</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $invoice->site?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500 break-all">{{ $invoice->site?->fqdn ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Provider Details</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Provider Invoice ID</div>
                        <div class="mt-1 break-all text-gray-900 dark:text-white">{{ $invoice->provider_invoice_id ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Provider Charge ID</div>
                        <div class="mt-1 break-all text-gray-900 dark:text-white">{{ $invoice->provider_charge_id ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Failure Reason</div>
                        <div class="mt-1 text-gray-900 dark:text-white">{{ $invoice->failure_reason ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($invoice->meta))
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Meta Payload</h2>
                <pre class="mt-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($invoice->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
</x-layouts.superadmin>
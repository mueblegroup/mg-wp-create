<x-layouts.superadmin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $provisioningLog->action }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $provisioningLog->created_at?->format('d M Y, h:i A') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Status" :value="ucfirst($provisioningLog->status)" />
            <x-superadmin.stat-card title="Site" :value="$provisioningLog->site?->name ?? '—'" />
            <x-superadmin.stat-card title="Plan" :value="$provisioningLog->site?->plan?->label ?? '—'" />
            <x-superadmin.stat-card title="Owner" :value="$provisioningLog->site?->user?->name ?? '—'" />
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Message</h2>
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300 break-words">
                {{ $provisioningLog->message }}
            </p>
        </div>

        @if ($provisioningLog->site)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Site</h2>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="font-medium text-gray-900 dark:text-white">{{ $provisioningLog->site->name }}</div>
                    <div class="text-gray-500 break-all">{{ $provisioningLog->site->fqdn }}</div>

                    <a href="{{ route('superadmin.sites.show', $provisioningLog->site) }}" class="inline-block pt-2 text-blue-600 hover:underline">
                        View Site
                    </a>
                </div>
            </div>
        @endif

        @if (! empty($provisioningLog->context))
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Context</h2>
                <pre class="mt-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($provisioningLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
    </div>
</x-layouts.superadmin>
<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->label }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $plan->name }}</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('superadmin.plans.edit', $plan) }}" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">Edit Plan</a>

                <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}" onsubmit="return confirm('Delete this plan? Only unused plans can be deleted.');">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Price" value="{{ $plan->currency }} {{ number_format($plan->price, 2) }}" />
            <x-superadmin.stat-card title="Level" :value="$plan->level" />
            <x-superadmin.stat-card title="Sites" :value="$plan->sites->count()" />
            <x-superadmin.stat-card title="Subscriptions" :value="$plan->subscriptions->count()" />
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Plan Details</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Custom Domain</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $plan->allows_custom_domain ? 'Allowed' : 'Not Allowed' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Max Themes</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $plan->max_themes }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Resource Profile</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $plan->resource_profile ?: '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Status</div>
                    <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $plan->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.superadmin>
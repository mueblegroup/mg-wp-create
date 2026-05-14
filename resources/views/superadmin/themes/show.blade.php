<x-layouts.superadmin>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $theme->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $theme->slug }}</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('superadmin.themes.edit', $theme) }}" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">Edit Theme</a>

                <form method="POST" action="{{ route('superadmin.themes.destroy', $theme) }}" onsubmit="return confirm('Delete this theme? Only unused themes can be deleted.');">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-superadmin.stat-card title="Min Plan Level" :value="$theme->min_plan_level" />
            <x-superadmin.stat-card title="ZIP Exists" :value="$theme->zip_exists ? 'Yes' : 'No'" />
            <x-superadmin.stat-card title="Sites" :value="$theme->sites->count()" />
            <x-superadmin.stat-card title="Status" :value="$theme->is_active ? 'Active' : 'Inactive'" />
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Theme Details</h2>

            <div class="mt-4 space-y-4 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">ZIP Path</div>
                    <div class="mt-1 break-all font-medium text-gray-900 dark:text-white">{{ $theme->zip_path ?: '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Preview Image</div>
                    <div class="mt-1 break-all font-medium text-gray-900 dark:text-white">{{ $theme->preview_image ?: '—' }}</div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Description</div>
                    <div class="mt-1 text-gray-700 dark:text-gray-300">{{ $theme->description ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.superadmin>
<x-layouts.superadmin>
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Theme</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $theme->name }}</p>
        </div>

        <form method="POST" action="{{ route('superadmin.themes.update', $theme) }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            @method('PUT')
            @include('superadmin.themes._form')

            <div class="flex justify-end gap-3">
                <a href="{{ route('superadmin.themes.show', $theme) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Cancel</a>
                <button class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">Save Changes</button>
            </div>
        </form>
    </div>
</x-layouts.superadmin>
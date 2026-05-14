<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Internal Name</label>
        <input name="name" value="{{ old('name', $plan->name) }}" placeholder="bronze"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Label</label>
        <input name="label" value="{{ old('label', $plan->label) }}" placeholder="Bronze"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('label') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price</label>
        <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price) }}"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('price') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
        <input name="currency" value="{{ old('currency', $plan->currency ?: 'MYR') }}" maxlength="3"
               class="mt-1 block w-full rounded-lg border-gray-300 uppercase dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('currency') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Themes</label>
        <input name="max_themes" type="number" min="0" value="{{ old('max_themes', $plan->max_themes) }}"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('max_themes') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('sort_order') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resource Profile</label>
        <input name="resource_profile" value="{{ old('resource_profile', $plan->resource_profile) }}" placeholder="bronze / silver / gold"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('resource_profile') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <label class="flex items-center gap-3">
        <input type="checkbox" name="allows_custom_domain" value="1" @checked(old('allows_custom_domain', $plan->allows_custom_domain))
               class="rounded border-gray-300">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Allow custom domain</span>
    </label>

    <label class="flex items-center gap-3">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))
               class="rounded border-gray-300">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
    </label>
</div>
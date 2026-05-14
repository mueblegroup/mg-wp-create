<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Theme Name</label>
        <input name="name" value="{{ old('name', $theme->name) }}" placeholder="Business Starter"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
        <input name="slug" value="{{ old('slug', $theme->slug) }}" placeholder="business-starter"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('slug') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Plan Level</label>
        <select name="min_plan_level" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            <option value="1" @selected(old('min_plan_level', $theme->min_plan_level) == 1)>Bronze / Level 1</option>
            <option value="2" @selected(old('min_plan_level', $theme->min_plan_level) == 2)>Silver / Level 2</option>
            <option value="3" @selected(old('min_plan_level', $theme->min_plan_level) == 3)>Gold / Level 3</option>
        </select>
        @error('min_plan_level') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Existing ZIP Path</label>
        <input name="zip_path" value="{{ old('zip_path', $theme->zip_path) }}" placeholder="theme.zip"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('zip_path') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Theme ZIP</label>
        <input name="zip_file" type="file" accept=".zip"
               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('zip_file') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preview Image Path</label>
        <input name="preview_image" value="{{ old('preview_image', $theme->preview_image) }}" placeholder="previews/theme.jpg"
               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('preview_image') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Preview Image</label>
        <input name="preview_file" type="file" accept="image/*"
               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        @error('preview_file') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
        <textarea name="description" rows="4"
                  class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('description', $theme->description) }}</textarea>
        @error('description') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
    </div>

    <label class="flex items-center gap-3 md:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $theme->is_active))
               class="rounded border-gray-300">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
    </label>
</div>
<x-app-layout>
    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Site</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Choose your package, theme, and subdomain to create your managed WordPress site.
                    </p>
                </div>

                <div class="border-b border-blue-200 bg-blue-50 px-6 py-4 text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                    In the current build, site provisioning is queued immediately after creation for testing. Later this will be triggered after successful billing confirmation.
                </div>

                @if ($themes->isEmpty())
                    <div class="border-b border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                        No valid theme ZIP packages were found in storage. Please upload your themes into <strong>storage/app/themes</strong> and make sure the database <code>zip_path</code> matches correctly.
                    </div>
                @endif

                <form method="POST" action="{{ route('sites.store') }}" class="space-y-6 px-6 py-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-black focus:ring-black dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                               placeholder="My Business Site">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subdomain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subdomain</label>
                        <div class="mt-1 flex rounded-lg shadow-sm">
                            <input type="text"
                                   id="subdomain"
                                   name="subdomain"
                                   value="{{ old('subdomain') }}"
                                   class="block w-full rounded-l-lg border-gray-300 focus:border-black focus:ring-black dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                   placeholder="mybrand">
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 px-4 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                .{{ $baseDomain }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Lowercase letters, numbers, and hyphens only.
                        </p>
                        @error('subdomain')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package</label>
                        <select id="plan_id"
                                name="plan_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-black focus:ring-black dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Select a package</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                    {{ $plan->label }} — RM {{ number_format($plan->price, 2) }}/month
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

            <div>
                <label for="theme_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Theme</label>
                <select id="theme_id"
                        name="theme_id"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-black focus:ring-black dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    <option value="">No Theme — Use default WordPress theme</option>
                    @foreach ($themes as $theme)
                        <option value="{{ $theme->id }}" @selected(old('theme_id') == $theme->id)>
                            {{ $theme->name }}
                            @if ($theme->min_plan_level === 2)
                                — Silver+
                            @elseif ($theme->min_plan_level === 3)
                                — Gold only
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('theme_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Leave this unselected to use the default WordPress theme.
                </p>
            </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                        <div class="font-semibold text-gray-900 dark:text-white">Provisioning flow</div>
                        <div class="mt-2 space-y-1">
                            <div>1. Site record is created</div>
                            <div>2. Provisioning job is queued</div>
                            <div>3. Hestia account and domain are created</div>
                            <div>4. WordPress is installed</div>
                            <div>5. Your selected theme is installed and activated</div>
                            <div>6. WordPress admin access is generated</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('sites.index') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Cancel
                        </a>

                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-gray-200"
                                @disabled($themes->isEmpty())>
                            Create Site
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
<x-layouts.superadmin>
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Site</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 break-all">{{ $site->fqdn }}</p>
        </div>

        <form method="POST" action="{{ route('superadmin.sites.update', $site) }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner</label>
                    <select name="user_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id', $site->user_id) == $user->id)>
                                {{ $user->name }} - {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                    <input name="name" value="{{ old('name', $site->name) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan</label>
                    <select name="plan_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id', $site->plan_id) == $plan->id)>
                                {{ $plan->label ?? $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Theme</label>
                    <select name="theme_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">No Theme</option>
                        @foreach ($themes as $theme)
                            <option value="{{ $theme->id }}" @selected(old('theme_id', $site->theme_id) == $theme->id)>
                                {{ $theme->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('theme_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Status</label>
                    <select name="status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach (['pending_payment', 'provisioning', 'active', 'suspended', 'failed'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $site->status) === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Status</label>
                    <select name="billing_status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">None</option>
                        @foreach (['pending', 'active', 'past_due', 'grace_period', 'suspended', 'cancelled', 'expired'] as $status)
                            <option value="{{ $status }}" @selected(old('billing_status', $site->billing_status) === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('billing_status') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary Domain</label>
                    <input name="primary_domain" value="{{ old('primary_domain', $site->primary_domain) }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('primary_domain') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <label class="flex items-center gap-3 md:col-span-2">
                    <input type="checkbox" name="custom_domain_enabled" value="1" @checked(old('custom_domain_enabled', $site->custom_domain_enabled)) class="rounded border-gray-300">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom domain enabled</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.sites.show', $site) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    Cancel
                </a>

                <button type="submit" class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-layouts.superadmin>
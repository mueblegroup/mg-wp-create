<x-layouts.superadmin>
    <div class="max-w-4xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Subscription</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Subscription #{{ $subscription->id }}
            </p>
        </div>

        <form method="POST" action="{{ route('superadmin.subscriptions.update', $subscription) }}"
              class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan</label>
                    <select name="plan_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id', $subscription->plan_id) == $plan->id)>
                                {{ $plan->label ?? $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach (['pending', 'active', 'past_due', 'grace_period', 'suspended', 'cancelled', 'expired'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $subscription->status) === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                    <input name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $subscription->amount) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('amount') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                    <input name="currency" maxlength="3" value="{{ old('currency', $subscription->currency ?: 'MYR') }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 uppercase dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('currency') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Cycle</label>
                    <select name="billing_cycle" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="monthly" @selected(old('billing_cycle', $subscription->billing_cycle) === 'monthly')>Monthly</option>
                        <option value="yearly" @selected(old('billing_cycle', $subscription->billing_cycle) === 'yearly')>Yearly</option>
                        <option value="annual" @selected(old('billing_cycle', $subscription->billing_cycle) === 'annual')>Annual</option>
                    </select>
                    @error('billing_cycle') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                @foreach (['starts_at', 'next_billing_at', 'last_paid_at', 'grace_ends_at'] as $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ ucwords(str_replace('_', ' ', $field)) }}
                        </label>
                        <input
                            name="{{ $field }}"
                            type="datetime-local"
                            value="{{ old($field, $subscription->{$field} ? $subscription->{$field}->format('Y-m-d\TH:i') : '') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                        @error($field) <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                @endforeach

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea name="notes" rows="4"
                              class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('notes', $subscription->notes) }}</textarea>
                    @error('notes') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('superadmin.subscriptions.show', $subscription) }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    Cancel
                </a>

                <button class="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-black">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</x-layouts.superadmin>
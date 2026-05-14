@props([
    'title',
    'value',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
        {{ $title }}
    </div>

    <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
        {{ $value }}
    </div>
</div>
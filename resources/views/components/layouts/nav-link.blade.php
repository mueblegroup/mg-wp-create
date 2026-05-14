@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   {{ $attributes->merge([
        'class' => $active
            ? 'flex items-center rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-black'
            : 'flex items-center rounded-xl px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'
   ]) }}>
    {{ $slot }}
</a>
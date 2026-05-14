<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Superadmin - {{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 dark:bg-gray-950">
    <div class="min-h-screen lg:flex">
        <aside class="w-full border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 lg:min-h-screen lg:w-72 lg:border-b-0 lg:border-r">
            <div class="flex h-16 items-center justify-between px-6">
                <a href="{{ route('superadmin.dashboard') }}" class="text-lg font-bold text-gray-900 dark:text-white">
                    Superadmin
                </a>

                <a href="{{ route('dashboard') }}" class="text-xs font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    Customer App
                </a>
            </div>

            <nav class="space-y-1 px-4 pb-6">
                <x-superadmin.nav-link :href="route('superadmin.dashboard')" :active="request()->routeIs('superadmin.dashboard')">
                    Overview
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.users.index')" :active="request()->routeIs('superadmin.users.*')">
                    Users
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.sites.index')" :active="request()->routeIs('superadmin.sites.*')">
                    Sites
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.plans.index')" :active="request()->routeIs('superadmin.plans.*')">
                    Plans
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.themes.index')" :active="request()->routeIs('superadmin.themes.*')">
                    Themes
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.subscriptions.index')" :active="request()->routeIs('superadmin.subscriptions.*')">
                    Subscriptions
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.invoices.index')" :active="request()->routeIs('superadmin.invoices.*')">
                    Invoices
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.payment-attempts.index')" :active="request()->routeIs('superadmin.payment-attempts.*')">
                    Transactions
                </x-superadmin.nav-link>

                <x-superadmin.nav-link :href="route('superadmin.provisioning-logs.index')" :active="request()->routeIs('superadmin.provisioning-logs.*')">
                    Provisioning Logs
                </x-superadmin.nav-link>
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Logged in as
                        </div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ auth()->user()->name }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <div class="p-6">
                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Mueble Playground') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-white">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-gray-950 dark:via-gray-950 dark:to-gray-900"></div>
        <div class="absolute left-1/2 top-0 -z-10 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-gray-200/50 blur-3xl dark:bg-gray-800/40"></div>

        <header class="border-b border-gray-200/70 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-black text-sm font-bold text-white dark:bg-white dark:text-black">
                        M
                    </div>
                    <div>
                        <div class="text-base font-semibold">{{ config('app.name', 'Mueble Playground') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Managed WordPress SaaS</div>
                    </div>
                </a>

                <nav class="hidden items-center gap-8 md:flex">
                    <a href="#features" class="text-sm font-medium text-gray-600 hover:text-black dark:text-gray-300 dark:hover:text-white">Features</a>
                    <a href="#plans" class="text-sm font-medium text-gray-600 hover:text-black dark:text-gray-300 dark:hover:text-white">Plans</a>
                    <a href="#how-it-works" class="text-sm font-medium text-gray-600 hover:text-black dark:text-gray-300 dark:hover:text-white">How It Works</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-gray-200 bg-white px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                            Launch WordPress sites faster
                        </div>

                        <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white sm:text-5xl lg:text-6xl">
                            Create and manage
                            <span class="block">WordPress sites like a SaaS.</span>
                        </h1>

                        <p class="mt-6 max-w-2xl text-base leading-7 text-gray-600 dark:text-gray-300 sm:text-lg">
                            Let your customers sign up, choose a package, pick a theme, and get a ready-to-use WordPress site
                            on your managed infrastructure. Built for subdomains, recurring billing, and scalable provisioning.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('sites.create') }}"
                                   class="inline-flex items-center justify-center rounded-2xl bg-black px-6 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                    Create Your First Site
                                </a>
                            @else
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                       class="inline-flex items-center justify-center rounded-2xl bg-black px-6 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                        Start Building
                                    </a>
                                @endif

                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center justify-center rounded-2xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900">
                                    Log In
                                </a>
                            @endauth
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="text-2xl font-bold">3</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Package tiers</div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="text-2xl font-bold">WP</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prebuilt themes</div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="text-2xl font-bold">24/7</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Managed infrastructure</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="rounded-[2rem] border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900">
                            <div class="rounded-[1.5rem] border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold">Site Provisioning Preview</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">From signup to WordPress access</div>
                                    </div>
                                    <div class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        Live Flow
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="text-xs uppercase tracking-[0.18em] text-gray-500">Step 1</div>
                                        <div class="mt-1 font-semibold">Choose package</div>
                                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bronze, Silver, or Gold</div>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="text-xs uppercase tracking-[0.18em] text-gray-500">Step 2</div>
                                        <div class="mt-1 font-semibold">Pick a theme</div>
                                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Theme selection based on plan tier</div>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="text-xs uppercase tracking-[0.18em] text-gray-500">Step 3</div>
                                        <div class="mt-1 font-semibold">Provision WordPress</div>
                                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Auto-created subdomain and WordPress admin</div>
                                    </div>

                                    <div class="rounded-2xl border border-black bg-black p-4 text-white dark:border-white dark:bg-white dark:text-black">
                                        <div class="text-xs uppercase tracking-[0.18em] opacity-70">Result</div>
                                        <div class="mt-1 font-semibold">Client receives site access</div>
                                        <div class="mt-1 text-sm opacity-80">Ready to log in and manage content</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -bottom-6 -left-6 hidden rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 lg:block">
                            <div class="text-xs uppercase tracking-[0.18em] text-gray-500">Managed Domain</div>
                            <div class="mt-1 text-sm font-semibold">yourbrand.sites.mueble-playground.cc</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-t border-gray-200 bg-gray-50/70 py-16 dark:border-gray-800 dark:bg-gray-900/40">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-bold tracking-tight">Everything needed for a managed WordPress SaaS</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Built for service providers who want to provision WordPress sites quickly without manually setting up every customer.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                            <div class="text-lg font-semibold">Managed Subdomains</div>
                            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Launch customer sites on your own domain structure with a simple onboarding flow.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                            <div class="text-lg font-semibold">Theme-Based Provisioning</div>
                            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Let customers choose from your curated theme catalog based on their selected package.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                            <div class="text-lg font-semibold">Recurring Billing Ready</div>
                            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Structured for subscription billing, payment tracking, and automated account lifecycle handling.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                            <div class="text-lg font-semibold">Customer Dashboard</div>
                            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Give users one central place to view their site, plan, domains, and WordPress admin access.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="plans" class="py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold tracking-tight">Simple package tiers</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Start small or go premium depending on how much flexibility and power your customer needs.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 lg:grid-cols-3">
                        <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">Bronze</div>
                            <div class="mt-4 text-4xl font-bold">RM 30</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">per month</div>

                            <ul class="mt-6 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                <li>Managed subdomain only</li>
                                <li>Up to 10 available themes</li>
                                <li>Starter resource profile</li>
                                <li>Good for basic launch sites</li>
                            </ul>
                        </div>

                        <div class="rounded-3xl border border-black bg-black p-8 text-white shadow-lg dark:border-white dark:bg-white dark:text-black">
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] opacity-70">Silver</div>
                            <div class="mt-4 text-4xl font-bold">RM 70</div>
                            <div class="mt-1 text-sm opacity-80">per month</div>

                            <ul class="mt-6 space-y-3 text-sm opacity-90">
                                <li>Custom domain support</li>
                                <li>Up to 20 available themes</li>
                                <li>Better resource allocation</li>
                                <li>Ideal for growing businesses</li>
                            </ul>
                        </div>

                        <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">Gold</div>
                            <div class="mt-4 text-4xl font-bold">RM 110</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">per month</div>

                            <ul class="mt-6 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                <li>Custom domain support</li>
                                <li>50+ premium theme options</li>
                                <li>Highest resource profile</li>
                                <li>Best for advanced client projects</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="border-t border-gray-200 py-16 dark:border-gray-800">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                        <div>
                            <h2 class="text-3xl font-bold tracking-tight">How it works</h2>
                            <p class="mt-4 text-gray-600 dark:text-gray-300">
                                Customers can onboard through a straightforward flow while you keep the infrastructure and provisioning controlled from one central Laravel app.
                            </p>

                            <div class="mt-8 space-y-5">
                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-black text-sm font-bold text-white dark:bg-white dark:text-black">1</div>
                                    <div>
                                        <div class="font-semibold">Sign up and choose a package</div>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            The customer registers, selects Bronze, Silver, or Gold, and proceeds into the site setup flow.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-black text-sm font-bold text-white dark:bg-white dark:text-black">2</div>
                                    <div>
                                        <div class="font-semibold">Select a plan-eligible theme</div>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            Available themes are filtered based on the package, so the customer only sees what their subscription allows.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-black text-sm font-bold text-white dark:bg-white dark:text-black">3</div>
                                    <div>
                                        <div class="font-semibold">Provision the site automatically</div>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            The platform prepares the site, connects the subdomain, and creates the WordPress admin account.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-black text-sm font-bold text-white dark:bg-white dark:text-black">4</div>
                                    <div>
                                        <div class="font-semibold">Customer logs in and manages the site</div>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            The client receives access details and can immediately begin managing content inside WordPress.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">Why this setup works</div>
                            <h3 class="mt-3 text-2xl font-bold">A scalable foundation for managed sites</h3>

                            <div class="mt-6 space-y-4 text-sm leading-7 text-gray-600 dark:text-gray-300">
                                <p>
                                    This platform structure is ideal for service businesses that want to offer ready-made WordPress websites
                                    without manually setting up every hosting account, domain mapping, and theme installation from scratch.
                                </p>
                                <p>
                                    It gives you a controlled onboarding process, clearer billing flow, and a path toward automated suspension,
                                    recovery, and plan-based provisioning as the project evolves.
                                </p>
                            </div>

                            <div class="mt-8">
                                @auth
                                    <a href="{{ route('dashboard') }}"
                                       class="inline-flex items-center rounded-2xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                        Go to Dashboard
                                    </a>
                                @else
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}"
                                           class="inline-flex items-center rounded-2xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-black dark:hover:bg-gray-200">
                                            Create an Account
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 bg-white py-8 dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 text-sm text-gray-500 dark:text-gray-400 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div>
                    © {{ now()->year }} {{ config('app.name', 'Mueble Playground') }}. All rights reserved.
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-black dark:hover:text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-black dark:hover:text-white">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hover:text-black dark:hover:text-white">Register</a>
                        @endif
                    @endauth
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
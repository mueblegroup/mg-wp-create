<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Jobs\ProvisionSiteJob;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Theme;
use App\Services\SiteDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SiteController extends Controller
{
    public function index(): View
    {
        $sites = auth()->user()
            ->sites()
            ->with(['plan', 'theme', 'subscription'])
            ->latest()
            ->paginate(10);

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $themes = Theme::query()
            ->where('is_active', true)
            ->orderBy('min_plan_level')
            ->orderBy('name')
            ->get()
            ->filter(function (Theme $theme) {
                return $theme->zip_exists;
            })
            ->values();

        return view('sites.create', [
            'plans' => $plans,
            'themes' => $themes,
            'baseDomain' => config('saas.base_domain'),
        ]);
    }

    public function store(StoreSiteRequest $request, BillingService $billingService): RedirectResponse
    {
        $user = $request->user();

        $plan = Plan::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('plan_id'));

        $theme = null;

        if ($request->filled('theme_id')) {
            $theme = Theme::query()
                ->where('is_active', true)
                ->findOrFail($request->integer('theme_id'));

            if (! $theme->zip_exists) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'theme_id' => 'The selected theme package file does not exist on the server.',
                    ]);
            }

            if (! $this->planCanUseTheme($plan, $theme)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'theme_id' => 'This theme is not available for the selected package.',
                    ]);
            }
        }

        $subdomain = Str::lower($request->string('subdomain')->toString());
        $fqdn = $subdomain . '.' . config('saas.base_domain');

        $slug = Str::slug($request->string('name')->toString()) . '-' . Str::lower(Str::random(6));
        $hestiaUsername = $this->generateHestiaUsername($user->id, $subdomain);

        $site = DB::transaction(function () use (
            $user,
            $plan,
            $theme,
            $request,
            $subdomain,
            $fqdn,
            $slug,
            $hestiaUsername
        ) {
            $site = Site::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'theme_id' => $theme?->id,
                'name' => $request->string('name')->toString(),
                'slug' => $slug,
                'subdomain' => $subdomain,
                'fqdn' => $fqdn,
                'hestia_username' => $hestiaUsername,
                'hestia_domain' => $fqdn,
                'wordpress_admin_email' => $user->email,
                'status' => Site::STATUS_PENDING_PAYMENT,
                'billing_status' => Subscription::STATUS_PENDING,
                'provisioning_error' => null,
                'provisioned_at' => null,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            $site->domains()->create([
                'domain' => $fqdn,
                'is_primary' => true,
                'is_verified' => true,
                'verification_status' => 'system',
                'verified_at' => now(),
            ]);

            Subscription::updateOrCreate(
                ['site_id' => $site->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'hitpay',
                    'amount' => $plan->price,
                    'currency' => $plan->currency ?? config('services.hitpay.currency', 'MYR'),
                    'billing_cycle' => $plan->billing_cycle ?? 'monthly',
                    'status' => Subscription::STATUS_PENDING,
                    'starts_at' => now(),
                ]
            );

            $site->provisioningLogs()->create([
                'action' => 'site_created',
                'status' => 'info',
                'message' => 'Site draft created. Customer is being redirected directly to payment.',
                'context' => [
                    'plan_id' => $plan->id,
                    'plan' => $plan->name,
                    'plan_label' => $plan->label,
                    'plan_level' => (int) $plan->sort_order,
                    'theme_id' => $theme?->id,
                    'theme' => $theme?->slug ?? 'none',
                    'theme_min_plan_level' => $theme?->min_plan_level,
                    'fqdn' => $fqdn,
                    'billing_status' => Subscription::STATUS_PENDING,
                ],
            ]);

            return $site;
        });

        try {
            $result = $billingService->startCheckout(
                user: $user,
                plan: $plan,
                site: $site,
                purpose: 'First payment for ' . $site->name
            );

            if (! empty($result['checkout_url'])) {
                return redirect()->away($result['checkout_url']);
            }

            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'Site draft created, but payment URL could not be generated. Please click Make Payment.');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'Site draft created, but payment could not start: ' . $e->getMessage());
        }
    }

    public function show(Site $site): View
    {
        abort_unless($site->user_id === auth()->id(), 403);

        $site->load([
            'plan',
            'theme',
            'domains',
            'subscription',
            'subscription.invoices',
            'provisioningLogs' => fn ($query) => $query->latest(),
        ]);

        return view('sites.show', compact('site'));
    }

    public function retryProvisioning(Site $site): RedirectResponse
    {
        abort_unless($site->user_id === auth()->id(), 403);

        if ($site->status === Site::STATUS_ACTIVE) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'This site is already active. Retry provisioning is not needed.');
        }

        if ($site->status === Site::STATUS_PROVISIONING) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'This site is already provisioning. Please wait for the current job to finish.');
        }

        if (($site->billing_status ?? null) !== Subscription::STATUS_ACTIVE) {
            return redirect()
                ->route('billing.index', ['site_id' => $site->id])
                ->with('error', 'Payment is required before provisioning can be retried.');
        }

        $site->load(['plan', 'theme']);

        if (! $site->plan || ! $site->plan->is_active) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'The selected package is no longer available.');
        }

        if ($site->theme) {
            if (! $site->theme->is_active) {
                return redirect()
                    ->route('sites.show', $site)
                    ->with('error', 'The selected theme is no longer active.');
            }

            if (! $site->theme->zip_exists) {
                return redirect()
                    ->route('sites.show', $site)
                    ->with('error', 'The selected theme package file does not exist on the server.');
            }

            if (! $this->planCanUseTheme($site->plan, $site->theme)) {
                return redirect()
                    ->route('sites.show', $site)
                    ->with('error', 'The selected theme is not available for this package.');
            }
        }

        $site->update([
            'status' => Site::STATUS_PENDING_PAYMENT,
            'provisioning_error' => null,
        ]);

        $site->provisioningLogs()->create([
            'action' => 'provisioning_retry_requested',
            'status' => 'info',
            'message' => 'Provisioning retry requested by the site owner after successful payment.',
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
                'plan_id' => $site->plan_id,
                'plan_level' => (int) $site->plan->sort_order,
                'theme_id' => $site->theme_id,
                'theme_min_plan_level' => $site->theme?->min_plan_level,
            ],
        ]);

        ProvisionSiteJob::dispatch($site->id);

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Provisioning has been requeued.');
    }

    public function destroy(Site $site, SiteDeletionService $siteDeletionService): RedirectResponse
    {
        abort_unless($site->user_id === auth()->id(), 403);

        try {
            $siteDeletionService->delete($site);

            return redirect()
                ->route('sites.index')
                ->with('success', 'Site deleted successfully.');
        } catch (Throwable $e) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'Failed to delete site: ' . $e->getMessage());
        }
    }

    protected function planCanUseTheme(Plan $plan, Theme $theme): bool
    {
        return (int) $theme->min_plan_level <= (int) $plan->sort_order;
    }

    protected function generateHestiaUsername(int $userId, string $subdomain): string
    {
        $clean = preg_replace('/[^a-z0-9]/', '', strtolower($subdomain));
        $base = 'u' . $userId . $clean;

        return substr($base, 0, 16);
    }
}
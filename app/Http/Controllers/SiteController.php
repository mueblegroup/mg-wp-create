<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Jobs\ProvisionSiteJob;
use App\Models\Plan;
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
            ->active()
            ->orderBy('min_plan_level')
            ->orderBy('name')
            ->get()
            ->filter(fn (Theme $theme) => $theme->zip_exists)
            ->values();

        return view('sites.create', [
            'plans' => $plans,
            'themes' => $themes,
            'baseDomain' => config('saas.base_domain'),
        ]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $plan = Plan::findOrFail($request->integer('plan_id'));
        $theme = $request->filled('theme_id')
            ? Theme::findOrFail($request->integer('theme_id'))
            : null;

        if ($theme && ! $theme->zip_exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'theme_id' => 'The selected theme package file does not exist on the server.',
                ]);
        }

        $subdomain = Str::lower($request->string('subdomain')->toString());
        $fqdn = $subdomain . '.' . config('saas.base_domain');
        $slug = Str::slug($request->string('name')->toString()) . '-' . Str::lower(Str::random(6));
        $hestiaUsername = $this->generateHestiaUsername($user->id, $subdomain);

        $site = DB::transaction(function () use ($user, $plan, $theme, $request, $subdomain, $fqdn, $slug, $hestiaUsername) {
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
            ]);

            $site->domains()->create([
                'domain' => $fqdn,
                'is_primary' => true,
                'is_verified' => true,
                'verification_status' => 'system',
                'verified_at' => now(),
            ]);

            Subscription::create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'plan_id' => $plan->id,
                'provider' => 'hitpay',
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => Subscription::STATUS_PENDING,
            ]);

            $site->provisioningLogs()->create([
                'action' => 'site_created',
                'status' => 'info',
                'message' => 'Site record created and pending payment/provisioning.',
                'context' => [
                    'plan' => $plan->name,
                    'theme' => $theme?->slug ?? 'none',
                    'fqdn' => $fqdn,
                ],
            ]);

            return $site;
        });

        ProvisionSiteJob::dispatch($site->id);

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site created successfully. Provisioning job has been queued.');
    }

    public function show(Site $site): View
    {
        abort_unless($site->user_id === auth()->id(), 403);

        $site->load([
            'plan',
            'theme',
            'domains',
            'subscription',
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

        $site->update([
            'status' => Site::STATUS_PENDING_PAYMENT,
            'provisioning_error' => null,
        ]);

        $site->provisioningLogs()->create([
            'action' => 'provisioning_requeued',
            'status' => 'info',
            'message' => 'Provisioning was manually requeued by the site owner.',
            'context' => [
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
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

    protected function generateHestiaUsername(int $userId, string $subdomain): string
    {
        $clean = preg_replace('/[^a-z0-9]/', '', strtolower($subdomain));
        $base = 'u' . $userId . $clean;

        return substr($base, 0, 16);
    }
}
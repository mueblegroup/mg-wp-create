<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionSiteJob;
use App\Jobs\SuspendSiteJob;
use App\Jobs\UnsuspendSiteJob;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Theme;
use App\Models\User;
use App\Services\SiteDeletionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));
        $billingStatus = trim((string) $request->get('billing_status'));

        $sites = Site::query()
            ->with(['user', 'plan', 'theme', 'subscription'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('fqdn', 'like', "%{$search}%")
                        ->orWhere('subdomain', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($billingStatus !== '', fn ($query) => $query->where('billing_status', $billingStatus))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.sites.index', compact('sites', 'search', 'status', 'billingStatus'));
    }

    public function show(Site $site): View
    {
        $site->load([
            'user',
            'plan',
            'theme',
            'domains',
            'subscription.plan',
            'subscription.invoices',
            'subscription.paymentAttempts',
            'subscription.paymentMethods',
            'provisioningLogs' => fn ($query) => $query->latest(),
            'paymentTransactions',
        ]);

        return view('superadmin.sites.show', compact('site'));
    }

    public function edit(Site $site): View
    {
        $users = User::query()->orderBy('name')->get();
        $plans = Plan::query()->orderBy('sort_order')->get();
        $themes = Theme::query()->orderBy('name')->get();

        return view('superadmin.sites.edit', compact('site', 'users', 'plans', 'themes'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'theme_id' => ['nullable', 'exists:themes,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:pending_payment,provisioning,active,suspended,failed'],
            'billing_status' => ['nullable', 'in:pending,active,past_due,grace_period,suspended,cancelled,expired'],
            'primary_domain' => ['nullable', 'string', 'max:255'],
            'custom_domain_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['custom_domain_enabled'] = $request->boolean('custom_domain_enabled');

        $site->update($validated);

        return redirect()
            ->route('superadmin.sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }

    public function retryProvisioning(Site $site): RedirectResponse
    {
        if ($site->status === Site::STATUS_PROVISIONING) {
            return back()->with('error', 'This site is already provisioning.');
        }

        if (($site->billing_status ?? null) !== Subscription::STATUS_ACTIVE) {
            return back()->with('error', 'This site does not have an active billing status.');
        }

        $site->update([
            'status' => Site::STATUS_PROVISIONING,
            'provisioning_error' => null,
        ]);

        $site->provisioningLogs()->create([
            'action' => 'superadmin_provisioning_retry',
            'status' => 'info',
            'message' => 'Provisioning retry was triggered by superadmin.',
            'context' => [
                'superadmin_id' => auth()->id(),
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
            ],
        ]);

        ProvisionSiteJob::dispatch($site->id);

        return back()->with('success', 'Provisioning retry queued.');
    }

    public function suspend(Site $site): RedirectResponse
    {
        if ($site->status === Site::STATUS_SUSPENDED) {
            return back()->with('error', 'This site is already suspended.');
        }

        SuspendSiteJob::dispatch($site->id);

        $site->provisioningLogs()->create([
            'action' => 'superadmin_suspend_requested',
            'status' => 'info',
            'message' => 'Site suspension was requested by superadmin.',
            'context' => [
                'superadmin_id' => auth()->id(),
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
            ],
        ]);

        return back()->with('success', 'Site suspension queued.');
    }

    public function unsuspend(Site $site): RedirectResponse
    {
        if ($site->status !== Site::STATUS_SUSPENDED) {
            return back()->with('error', 'Only suspended sites can be unsuspended.');
        }

        UnsuspendSiteJob::dispatch($site->id);

        $site->provisioningLogs()->create([
            'action' => 'superadmin_unsuspend_requested',
            'status' => 'info',
            'message' => 'Site unsuspension was requested by superadmin.',
            'context' => [
                'superadmin_id' => auth()->id(),
                'site_id' => $site->id,
                'fqdn' => $site->fqdn,
            ],
        ]);

        return back()->with('success', 'Site unsuspension queued.');
    }

    public function destroy(Site $site, SiteDeletionService $siteDeletionService): RedirectResponse
    {
        try {
            $siteDeletionService->delete($site);

            return redirect()
                ->route('superadmin.sites.index')
                ->with('success', 'Site deleted successfully.');
        } catch (Throwable $e) {
            return back()->with('error', 'Failed to delete site: ' . $e->getMessage());
        }
    }
}
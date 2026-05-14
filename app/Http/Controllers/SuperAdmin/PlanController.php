<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));

        $plans = Plan::query()
            ->withCount(['sites', 'subscriptions'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('resource_profile', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('price')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.plans.index', compact('plans', 'search', 'status'));
    }

    public function create(): View
    {
        $plan = new Plan([
            'currency' => 'MYR',
            'sort_order' => 1,
            'is_active' => true,
            'allows_custom_domain' => false,
            'max_themes' => 10,
        ]);

        return view('superadmin.plans.create', compact('plan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        Plan::create($validated);

        return redirect()
            ->route('superadmin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan): View
    {
        $plan->load([
            'sites.user',
            'subscriptions.user',
            'subscriptions.site',
        ]);

        return view('superadmin.plans.show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan);

        $plan->update($validated);

        return redirect()
            ->route('superadmin.plans.show', $plan)
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->sites()->exists()) {
            return back()->with('error', 'This plan has sites attached. Disable it instead of deleting.');
        }

        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'This plan has subscriptions attached. Disable it instead of deleting.');
        }

        $plan->delete();

        return redirect()
            ->route('superadmin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    protected function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $planId = $plan?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:plans,name,' . $planId],
            'label' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'allows_custom_domain' => ['nullable', 'boolean'],
            'max_themes' => ['required', 'integer', 'min:0'],
            'resource_profile' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);
        $validated['allows_custom_domain'] = $request->boolean('allows_custom_domain');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
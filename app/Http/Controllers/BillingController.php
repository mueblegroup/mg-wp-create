<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\StartCheckoutRequest;
use App\Models\Plan;
use App\Models\Site;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billingService) {}

    public function index(Request $request): View
    {
        $subscriptions = $request->user()->subscriptions()->with(['plan', 'site'])->latest()->get();
        $invoices = $request->user()->invoices()->with('subscription')->latest()->limit(20)->get();

        return view('billing.index', compact('subscriptions', 'invoices'));
    }

    public function checkout(StartCheckoutRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', $request->user()->id)->findOrFail($request->integer('site_id'));
        $plan = Plan::findOrFail($request->integer('plan_id'));

        $result = $this->billingService->startCheckout($request->user(), $plan, $site);

        return redirect()->away($result['checkout_url']);
    }

    public function success(): View
    {
        return view('billing.success');
    }

    public function cancel(): View
    {
        return view('billing.cancel');
    }
}
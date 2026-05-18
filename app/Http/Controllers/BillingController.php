<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\StartCheckoutRequest;
use App\Models\Plan;
use App\Models\Site;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Invoice;
use App\Models\Subscription;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billingService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $sites = $user->sites()->latest()->get();
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get();
        $subscriptions = $user->subscriptions()->with(['plan', 'site'])->latest()->get();
        $invoices = $user->invoices()->with(['subscription.plan', 'subscription.site'])->latest()->limit(20)->get();
        $selectedSiteId = $request->integer('site_id');

        return view('billing.index', compact(
            'sites',
            'plans',
            'subscriptions',
            'invoices',
            'selectedSiteId'
        ));
    }

    public function checkout(StartCheckoutRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', $request->user()->id)
            ->findOrFail($request->integer('site_id'));

        $plan = Plan::findOrFail($request->integer('plan_id'));

        $result = $this->billingService->startCheckout($request->user(), $plan, $site);

        if (empty($result['checkout_url'])) {
            return back()->with('error', 'Unable to generate HitPay checkout URL.');
        }

        return redirect()->away($result['checkout_url']);
    }

    public function success(): View
    {
        return view('billing.success');
    }

    public function payInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return back()->with('error', 'This invoice is not pending payment.');
        }

        $result = $this->billingService->startInvoicePayment($request->user(), $invoice);

        if (empty($result['checkout_url'])) {
            return back()->with('error', 'Unable to generate HitPay payment URL.');
        }

        return redirect()->away($result['checkout_url']);
    }

    public function cancel(): View
    {
        return view('billing.cancel');
    }
}
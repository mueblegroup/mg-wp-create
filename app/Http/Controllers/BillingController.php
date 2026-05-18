<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\StartCheckoutRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Site;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billingService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $sites = $user->sites()
            ->with(['plan', 'subscription'])
            ->latest()
            ->get();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $subscriptions = $user->subscriptions()
            ->with(['plan', 'site'])
            ->latest()
            ->get();

        $invoices = $user->invoices()
            ->with(['subscription.plan', 'subscription.site', 'site'])
            ->latest()
            ->limit(20)
            ->get();

        $selectedSiteId = $request->integer('site_id');

        return view('billing.index', compact(
            'sites',
            'plans',
            'subscriptions',
            'invoices',
            'selectedSiteId'
        ));
    }

    /**
     * Upgrade / downgrade checkout.
     *
     * New site creation should NOT come here anymore.
     * Renewals should preferably use paySite() or payInvoice().
     */
    public function checkout(StartCheckoutRequest $request): RedirectResponse
    {
        $site = Site::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($request->integer('site_id'));

        $plan = Plan::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('plan_id'));

        try {
            $result = $this->billingService->startCheckout(
                user: $request->user(),
                plan: $plan,
                site: $site,
                purpose: 'Package payment for ' . $site->name
            );

            if (empty($result['checkout_url'])) {
                return back()->with('error', 'Unable to generate HitPay checkout URL.');
            }

            return redirect()->away($result['checkout_url']);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    /**
     * One-click payment from site page.
     *
     * This uses the site's current subscription/plan and sends user directly to HitPay.
     */
    public function paySite(Request $request, Site $site): RedirectResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        try {
            $result = $this->billingService->startSitePayment($request->user(), $site);

            if (empty($result['checkout_url'])) {
                return back()->with('error', 'Unable to generate HitPay payment URL.');
            }

            return redirect()->away($result['checkout_url']);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    public function payInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return back()->with('error', 'This invoice is not pending payment.');
        }

        try {
            $result = $this->billingService->startInvoicePayment($request->user(), $invoice);

            if (empty($result['checkout_url'])) {
                return back()->with('error', 'Unable to generate HitPay payment URL.');
            }

            return redirect()->away($result['checkout_url']);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
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
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentAttemptController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));

        $paymentAttempts = PaymentAttempt::query()
            ->with(['subscription.user', 'subscription.site', 'subscription.plan', 'invoice'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('provider_event_type', 'like', "%{$search}%")
                        ->orWhere('provider_charge_id', 'like', "%{$search}%")
                        ->orWhere('provider_reference', 'like', "%{$search}%")
                        ->orWhereHas('subscription.user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subscription.site', function ($siteQuery) use ($search) {
                            $siteQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('fqdn', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.payment-attempts.index', compact(
            'paymentAttempts',
            'search',
            'status'
        ));
    }

    public function show(PaymentAttempt $paymentAttempt): View
    {
        $paymentAttempt->load([
            'subscription.user',
            'subscription.site',
            'subscription.plan',
            'invoice',
        ]);

        return view('superadmin.payment-attempts.show', compact('paymentAttempt'));
    }
}
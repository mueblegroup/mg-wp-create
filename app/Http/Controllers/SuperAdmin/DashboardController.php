<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_sites' => Site::count(),
            'active_sites' => Site::where('status', Site::STATUS_ACTIVE)->count(),
            'provisioning_sites' => Site::where('status', Site::STATUS_PROVISIONING)->count(),
            'failed_sites' => Site::where('status', Site::STATUS_FAILED)->count(),
            'suspended_sites' => Site::where('status', Site::STATUS_SUSPENDED)->count(),

            'active_subscriptions' => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            'pending_subscriptions' => Subscription::where('status', Subscription::STATUS_PENDING)->count(),
            'past_due_subscriptions' => Subscription::where('status', Subscription::STATUS_PAST_DUE)->count(),

            'paid_invoices' => Invoice::where('status', Invoice::STATUS_PAID)->count(),
            'pending_invoices' => Invoice::where('status', Invoice::STATUS_PENDING)->count(),
            'failed_invoices' => Invoice::where('status', Invoice::STATUS_FAILED)->count(),

            'mrr' => Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->where('billing_cycle', 'monthly')
                ->sum('amount'),

            'arr' => Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->where('billing_cycle', 'yearly')
                ->sum('amount'),
        ];

        $recentUsers = User::latest()->limit(8)->get();

        $recentSites = Site::with(['user', 'plan', 'theme', 'subscription'])
            ->latest()
            ->limit(10)
            ->get();

        $recentInvoices = Invoice::with(['user', 'site', 'subscription.plan'])
            ->latest()
            ->limit(10)
            ->get();

        $failedSites = Site::with(['user', 'plan'])
            ->where('status', Site::STATUS_FAILED)
            ->latest()
            ->limit(8)
            ->get();

        return view('superadmin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentSites',
            'recentInvoices',
            'failedSites'
        ));
    }
}
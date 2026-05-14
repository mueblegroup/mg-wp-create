<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));

        $invoices = Invoice::query()
            ->with(['user', 'site', 'subscription.plan'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('provider_invoice_id', 'like', "%{$search}%")
                        ->orWhere('provider_charge_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('site', function ($siteQuery) use ($search) {
                            $siteQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('fqdn', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.invoices.index', compact('invoices', 'search', 'status'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'user',
            'site',
            'subscription.plan',
            'subscription.paymentAttempts',
        ]);

        return view('superadmin.invoices.show', compact('invoice'));
    }
}
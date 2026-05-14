<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ProvisioningLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvisioningLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));

        $provisioningLogs = ProvisioningLog::query()
            ->with(['site.user', 'site.plan'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('site', function ($siteQuery) use ($search) {
                            $siteQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('fqdn', 'like', "%{$search}%");
                        })
                        ->orWhereHas('site.user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.provisioning-logs.index', compact(
            'provisioningLogs',
            'search',
            'status'
        ));
    }

    public function show(ProvisioningLog $provisioningLog): View
    {
        $provisioningLog->load(['site.user', 'site.plan', 'site.theme']);

        return view('superadmin.provisioning-logs.show', compact('provisioningLog'));
    }
}
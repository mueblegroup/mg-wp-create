<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $sites = $user->sites()
            ->with(['plan', 'theme'])
            ->latest()
            ->get();

        return view('dashboard', [
            'sitesCount' => $sites->count(),
            'activeSitesCount' => $sites->where('status', Site::STATUS_ACTIVE)->count(),
            'suspendedSitesCount' => $sites->where('status', Site::STATUS_SUSPENDED)->count(),
            'sites' => $sites->take(5),
        ]);
    }
}
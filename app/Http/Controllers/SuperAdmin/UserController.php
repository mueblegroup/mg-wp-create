<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $role = trim((string) $request->get('role'));

        $users = User::query()
            ->withCount(['sites', 'subscriptions', 'invoices'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.users.index', compact('users', 'search', 'role'));
    }

    public function show(User $user): View
    {
        $user->load([
            'sites.plan',
            'sites.theme',
            'sites.subscription',
            'subscriptions.plan',
            'subscriptions.site',
            'invoices.subscription.plan',
            'invoices.site',
            'paymentMethods',
        ]);

        return view('superadmin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('superadmin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:user,superadmin'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('superadmin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own superadmin account.');
        }

        if ($user->sites()->exists()) {
            return back()->with('error', 'This user has sites. Delete or transfer the sites first.');
        }

        $user->delete();

        return redirect()
            ->route('superadmin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
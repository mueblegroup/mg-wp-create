<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class WordPressSsoController extends Controller
{
    public function redirect(Site $site): RedirectResponse
    {
        abort_unless($site->user_id === auth()->id(), 403);

        if ($site->status !== Site::STATUS_ACTIVE) {
            return back()->with('error', 'This WordPress site is not active yet.');
        }

        if (! $site->fqdn || ! $site->wordpress_admin_username || ! $site->wordpress_admin_email) {
            return back()->with('error', 'WordPress admin login is not ready for this site.');
        }

        if (! $site->wordpress_sso_secret) {
            return back()->with('error', 'WordPress SSO is not configured for this site.');
        }

        $expires = now()->addMinutes(2)->timestamp;
        $nonce = Str::random(32);

        $payload = implode('|', [
            $site->id,
            $site->wordpress_admin_username,
            $site->wordpress_admin_email,
            $expires,
            $nonce,
        ]);

        $signature = hash_hmac('sha256', $payload, $site->wordpress_sso_secret);

        $loginUrl = 'https://' . $site->fqdn . '/wp-admin/admin-post.php?' . http_build_query([
            'action' => 'mg_sso_login',
            'site_id' => $site->id,
            'username' => $site->wordpress_admin_username,
            'email' => $site->wordpress_admin_email,
            'expires' => $expires,
            'nonce' => $nonce,
            'signature' => $signature,
        ]);

        return redirect()->away($loginUrl);
    }
}
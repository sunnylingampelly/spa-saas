<?php

namespace App\Domain\Auth\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Laravel's redirect()->intended() blindly trusts whatever URL was last
 * stashed in the session, even if it belongs to a different role's area
 * (e.g. a guest hit /dashboard, got bounced to login, then signed in as
 * super_admin — intended() would send them back to the spa-owner-only
 * /dashboard and they'd immediately 403). This validates the intended URL
 * against the authenticating user's role before trusting it.
 */
class PostLoginRedirect
{
    public static function for(Request $request, User $user): RedirectResponse
    {
        $isSuperAdmin = $user->hasRole('super_admin');
        $home = $isSuperAdmin ? route('admin.dashboard') : route('dashboard');

        $intendedUrl = $request->session()->pull('url.intended');

        if ($intendedUrl && self::intendedUrlMatchesRole($intendedUrl, $isSuperAdmin)) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->to($home);
    }

    private static function intendedUrlMatchesRole(string $url, bool $isSuperAdmin): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $isAdminPath = str_starts_with($path, '/admin');

        return $isSuperAdmin ? $isAdminPath : ! $isAdminPath;
    }
}

<?php

namespace App\Http\Responses;

use App\Domain\Auth\Services\PostLoginRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;

/**
 * Fortify's default implementation redirects to config('fortify.home') (hardcoded '/dashboard')
 * whenever there's no stashed "intended" URL — which is exactly what happens when a super admin
 * navigates straight to a password-confirmation-gated page (e.g. /admin/two-factor-setup) rather
 * than being bounced there from elsewhere. That sent every super admin to the spa-owner-only
 * /dashboard and 403'd them. Reuses the same role-aware redirect already used after login.
 */
class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        return PostLoginRedirect::for($request, $request->user());
    }
}

<?php

namespace App\Http\Responses;

use App\Domain\Auth\Services\PostLoginRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * The initial-password login and the 2FA-challenge login are two separate Fortify contracts
 * (LoginResponse vs. TwoFactorLoginResponse) — only the former was made role-aware previously.
 * Once a super admin actually finishes enabling 2FA, every subsequent login completes via this
 * response instead, which would otherwise fall back to Fortify's default '/dashboard' redirect
 * and 403 them exactly like the password-confirmation bug did.
 */
class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return PostLoginRedirect::for($request, $request->user());
    }
}

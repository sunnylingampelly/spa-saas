<?php

namespace App\Http\Responses;

use App\Domain\Auth\Services\PostLoginRedirect;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return PostLoginRedirect::for($request, $request->user());
    }
}

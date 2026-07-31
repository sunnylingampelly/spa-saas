<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorSetupController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('SuperAdmin/TwoFactorSetup', [
            'twoFactorEnabled' => $request->user()->two_factor_confirmed_at !== null,
        ]);
    }
}

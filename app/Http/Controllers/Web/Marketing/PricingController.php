<?php

namespace App\Http\Controllers\Web\Marketing;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Marketing/Pricing', [
            'plans' => config('subscriptions.plans'),
            'trialDays' => config('subscriptions.trial_days'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domain\Tenancy\Actions\CreateSpaAction;
use App\Domain\Tenancy\DTOs\CreateSpaData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreateSpaController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Onboarding/CreateSpa');
    }

    public function store(Request $request, CreateSpaAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Required: phone is the primary contact for a business account, and state
            // determines CGST/SGST vs IGST on every invoice this spa ever issues.
            'phone' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        $spa = $action->execute(new CreateSpaData(
            ownerUserId: $request->user()->id,
            name: $data['name'],
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            gstNumber: $data['gst_number'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
        ));

        $request->session()->put('current_spa_id', $spa->id);

        return redirect()->route('dashboard');
    }
}

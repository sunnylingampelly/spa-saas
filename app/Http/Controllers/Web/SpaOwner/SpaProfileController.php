<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpaProfileController extends Controller
{
    public function show(TenantContext $tenantContext): Response
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('view', $spa);

        return Inertia::render('SpaProfile/Show', [
            'spa' => $spa,
        ]);
    }

    public function update(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'pan_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'google_maps_link' => ['nullable', 'url', 'max:2048'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'invoice_footer_note' => ['nullable', 'string', 'max:500'],
        ]);

        $spa->update($data);

        return back()->with('success', 'Spa profile updated.');
    }
}

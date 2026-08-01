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
            'razorpayKeyId' => $spa->razorpay_key_id,
            'razorpayConfigured' => filled($spa->razorpay_key_id) && filled($spa->razorpay_key_secret),
            'razorpayWebhookUrl' => route('webhooks.razorpay.spa', $spa->razorpay_webhook_token),
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

    public function updatePaymentSettings(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $data = $request->validate([
            'razorpay_key_id' => ['nullable', 'string', 'max:255'],
            'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        // Secrets never round-trip to the browser (Spa::$hidden), so the form fields are always
        // blank on load — a blank submission means "leave the existing secret untouched," not
        // "clear it." Only a non-empty value replaces what's already saved.
        $updates = ['razorpay_key_id' => $data['razorpay_key_id'] ?? null];

        if (filled($data['razorpay_key_secret'] ?? null)) {
            $updates['razorpay_key_secret'] = $data['razorpay_key_secret'];
        }

        if (filled($data['razorpay_webhook_secret'] ?? null)) {
            $updates['razorpay_webhook_secret'] = $data['razorpay_webhook_secret'];
        }

        $spa->update($updates);

        return back()->with('success', 'Payment settings updated.');
    }

    public function disconnectPaymentSettings(TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $spa->update([
            'razorpay_key_id' => null,
            'razorpay_key_secret' => null,
            'razorpay_webhook_secret' => null,
        ]);

        return back()->with('success', 'Razorpay disconnected.');
    }
}

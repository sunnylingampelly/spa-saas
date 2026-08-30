<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Tenancy\Services\SpaMailer;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
            'smtpConfigured' => SpaMailer::isConfigured($spa),
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

    public function updateEmailSettings(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $data = $request->validate([
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:10'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        // smtp_password never round-trips to the browser (Spa::$hidden), so — same rule as
        // the Razorpay secrets above — a blank submission means "leave it untouched," not
        // "clear it."
        $updates = collect($data)->except('smtp_password')->all();

        if (filled($data['smtp_password'] ?? null)) {
            $updates['smtp_password'] = $data['smtp_password'];
        }

        $spa->update($updates);

        return back()->with('success', 'Email settings updated.');
    }

    public function disconnectEmailSettings(TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $spa->update([
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => null,
            'mail_from_address' => null,
            'mail_from_name' => null,
        ]);

        return back()->with('success', 'Custom SMTP disconnected — campaigns will send through the platform mailer again.');
    }

    public function sendTestEmail(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $to = $request->user()->email;

        try {
            Mail::mailer(SpaMailer::mailerFor($spa))->raw(
                "This is a test email from {$spa->name}'s SpaOrbit email settings — if you're reading this, your SMTP configuration works.",
                fn ($message) => $message->to($to)->subject('SpaOrbit test email'),
            );
        } catch (Throwable $e) {
            return back()->with('error', "Couldn't send a test email: {$e->getMessage()}");
        }

        return back()->with('success', "Test email sent to {$to}.");
    }
}

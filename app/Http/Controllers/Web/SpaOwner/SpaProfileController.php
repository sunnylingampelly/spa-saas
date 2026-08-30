<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Tenancy\Services\SpaMailer;
use App\Domain\Tenancy\Services\TenantContext;
use App\Domain\WhatsApp\Exceptions\WhatsAppApiException;
use App\Domain\WhatsApp\Services\WhatsAppClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SpaProfileController extends Controller
{
    public function show(TenantContext $tenantContext): Response
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('view', $spa);

        // Spas created before the WhatsApp connection existed have no token yet — self-heals
        // here rather than needing a backfill migration, since it's only ever needed once
        // someone actually opens Settings.
        if (blank($spa->whatsapp_webhook_token)) {
            $spa->update(['whatsapp_webhook_token' => Str::random(40)]);
        }

        return Inertia::render('SpaProfile/Show', [
            'spa' => $spa,
            'razorpayKeyId' => $spa->razorpay_key_id,
            'razorpayConfigured' => filled($spa->razorpay_key_id) && filled($spa->razorpay_key_secret),
            'razorpayWebhookUrl' => route('webhooks.razorpay.spa', $spa->razorpay_webhook_token),
            'smtpConfigured' => SpaMailer::isConfigured($spa),
            'whatsappConfigured' => WhatsAppClient::forSpa($spa)->isConfigured(),
            'whatsappWebhookUrl' => route('webhooks.whatsapp.verify', $spa->whatsapp_webhook_token),
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

    public function updateWhatsAppSettings(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $data = $request->validate([
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:255'],
            'whatsapp_business_account_id' => ['nullable', 'string', 'max:255'],
            'whatsapp_access_token' => ['nullable', 'string', 'max:2000'],
        ]);

        // whatsapp_access_token never round-trips to the browser (Spa::$hidden), so — same rule
        // as the Razorpay/SMTP secrets — a blank submission means "leave it untouched," not
        // "clear it."
        $updates = collect($data)->except('whatsapp_access_token')->all();

        if (filled($data['whatsapp_access_token'] ?? null)) {
            $updates['whatsapp_access_token'] = $data['whatsapp_access_token'];
        }

        $spa->update($updates);

        return back()->with('success', 'WhatsApp settings updated. Use "Test Connection" to confirm they work.');
    }

    public function disconnectWhatsAppSettings(TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        $spa->update([
            'whatsapp_phone_number_id' => null,
            'whatsapp_business_account_id' => null,
            'whatsapp_access_token' => null,
            'whatsapp_display_phone_number' => null,
            'whatsapp_verified_name' => null,
        ]);

        return back()->with('success', 'WhatsApp disconnected — campaigns can no longer be sent until reconnected.');
    }

    public function testWhatsAppConnection(TenantContext $tenantContext): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        $this->authorize('update', $spa);

        try {
            $details = WhatsAppClient::forSpa($spa)->fetchPhoneNumberDetails();
        } catch (WhatsAppApiException $e) {
            return back()->with('error', "Couldn't connect to WhatsApp: {$e->getMessage()}");
        }

        $spa->update([
            'whatsapp_display_phone_number' => $details['display_phone_number'],
            'whatsapp_verified_name' => $details['verified_name'],
        ]);

        return back()->with('success', "Connected to {$details['display_phone_number']} ({$details['verified_name']}).");
    }
}

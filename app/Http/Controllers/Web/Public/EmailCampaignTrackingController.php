<?php

namespace App\Http\Controllers\Web\Public;

use App\Domain\Marketing\Models\EmailCampaignRecipient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Fully public — reached only via an unguessable EmailCampaignRecipient::tracking_token,
 * never behind auth or spa.context. Same trust model as Public\InvoicePaymentController:
 * TenantScope is a no-op with no active tenant context, so the token itself (bound via
 * {recipient:tracking_token}) is the sole access-control boundary.
 */
class EmailCampaignTrackingController extends Controller
{
    // A 1x1 transparent GIF — the standard email tracking-pixel payload.
    private const PIXEL_GIF_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7';

    public function trackOpen(EmailCampaignRecipient $recipient): Response
    {
        if ($recipient->opened_at === null) {
            $recipient->update(['opened_at' => now()]);
            $recipient->campaign()->increment('opened_count');
        }

        $recipient->increment('open_count');

        return response(base64_decode(self::PIXEL_GIF_BASE64), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function trackClick(Request $request, EmailCampaignRecipient $recipient): RedirectResponse
    {
        $url = $request->string('url')->toString();

        // Never redirect anywhere but a real http(s) URL — this endpoint is otherwise an
        // open redirect, since the ?url= param round-trips through an email we don't fully
        // control the contents of.
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            abort(404);
        }

        if ($recipient->clicked_at === null) {
            $recipient->update(['clicked_at' => now()]);
            $recipient->campaign()->increment('clicked_count');
        }

        $recipient->increment('click_count');

        return redirect()->away($url);
    }

    public function unsubscribe(EmailCampaignRecipient $recipient): InertiaResponse
    {
        if ($recipient->unsubscribed_at === null) {
            $recipient->update(['unsubscribed_at' => now()]);
            $recipient->campaign()->increment('unsubscribed_count');

            $recipient->customer?->update([
                'marketing_opt_out' => true,
                'marketing_opt_out_at' => now(),
            ]);
        }

        return Inertia::render('Public/Unsubscribed');
    }
}

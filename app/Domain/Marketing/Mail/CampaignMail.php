<?php

namespace App\Domain\Marketing\Mail;

use App\Domain\Marketing\Models\EmailCampaignRecipient;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CampaignMail extends Mailable
{
    public function __construct(public readonly EmailCampaignRecipient $recipient) {}

    public function envelope(): Envelope
    {
        $spaName = $this->recipient->campaign->spa?->name;

        return new Envelope(
            from: $spaName ? new Address(config('mail.from.address'), $spaName) : null,
            subject: $this->recipient->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderedHtml());
    }

    private function renderedHtml(): string
    {
        $campaign = $this->recipient->campaign;
        $customerName = $this->recipient->customer?->name ?? 'there';

        $body = str_replace('{{customer_name}}', e($customerName), $campaign->body_html);
        $body = $this->rewriteLinksForClickTracking($body);
        $body .= $this->trackingPixelHtml();
        $body .= $this->unsubscribeFooterHtml();

        return $body;
    }

    // Every <a href="http(s)://..."> is routed through the click-tracking redirect first —
    // mailto:/anchor links are left alone since there's nowhere useful to redirect them from.
    private function rewriteLinksForClickTracking(string $html): string
    {
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>/i',
            function (array $matches): string {
                [, $before, $url, $after] = $matches;

                if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                    return $matches[0];
                }

                $trackedUrl = route('public.email-campaigns.track-click', [
                    'recipient' => $this->recipient->tracking_token,
                    'url' => $url,
                ]);

                return "<a {$before}href=\"{$trackedUrl}\"{$after}>";
            },
            $html,
        );
    }

    private function trackingPixelHtml(): string
    {
        $url = route('public.email-campaigns.track-open', ['recipient' => $this->recipient->tracking_token]);

        return "<img src=\"{$url}\" width=\"1\" height=\"1\" alt=\"\" style=\"display:none;border:0;\">";
    }

    private function unsubscribeFooterHtml(): string
    {
        $url = route('public.email-campaigns.unsubscribe', ['recipient' => $this->recipient->tracking_token]);

        return '<p style="margin-top:24px;font-size:12px;color:#94a3b8;text-align:center;">'
            ."<a href=\"{$url}\" style=\"color:#94a3b8;\">Unsubscribe from marketing emails</a>"
            .'</p>';
    }
}

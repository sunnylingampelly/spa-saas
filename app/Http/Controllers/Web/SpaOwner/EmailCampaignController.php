<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Marketing\Actions\BuildCampaignAudienceAction;
use App\Domain\Marketing\Actions\CreateEmailCampaignAction;
use App\Domain\Marketing\Actions\SendEmailCampaignAction;
use App\Domain\Marketing\Models\EmailCampaign;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmailCampaignController extends Controller
{
    private const STARTER_TEMPLATES = [
        [
            'name' => 'Simple announcement',
            'category' => 'Announcements',
            'subject' => "We've got something new at {spa}",
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Hi {{customer_name}},</h1>
    <p style="font-size:15px;line-height:1.6;">We wanted to let you know about something new — [describe your announcement here].</p>
    <p style="font-size:15px;line-height:1.6;">Come see us soon — we'd love to have you in.</p>
    <p style="font-size:15px;">See you soon!</p>
</div>
HTML,
        ],
        [
            'name' => 'Promotional offer',
            'category' => 'Promotions',
            'subject' => 'A little something just for you',
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Hi {{customer_name}},</h1>
    <p style="font-size:15px;line-height:1.6;">For a limited time, enjoy <strong>[offer, e.g. 20% off]</strong> on [service name].</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="https://your-booking-link.example.com" style="background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Book Now</a>
    </p>
    <p style="font-size:13px;color:#64748b;">Offer valid until [date].</p>
</div>
HTML,
        ],
        [
            'name' => "We've missed you",
            'category' => 'Win-back',
            'subject' => 'It\'s been a while — come back and relax',
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Hi {{customer_name}},</h1>
    <p style="font-size:15px;line-height:1.6;">It's been a while since your last visit — we'd love to see you again.</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="https://your-booking-link.example.com" style="background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Book Your Next Visit</a>
    </p>
</div>
HTML,
        ],
        [
            'name' => 'Thank you for visiting',
            'category' => 'Retention',
            'subject' => 'Thank you for visiting {spa}',
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Thank you, {{customer_name}}!</h1>
    <p style="font-size:15px;line-height:1.6;">It was a pleasure having you with us. We hope you left feeling relaxed and refreshed.</p>
    <p style="font-size:15px;line-height:1.6;">We'd love to hear how it went — and we can't wait to welcome you back.</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="https://your-booking-link.example.com" style="background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Book Your Next Visit</a>
    </p>
</div>
HTML,
        ],
        [
            'name' => 'Birthday treat',
            'category' => 'Retention',
            'subject' => 'Happy Birthday from all of us at {spa}',
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Happy Birthday, {{customer_name}}!</h1>
    <p style="font-size:15px;line-height:1.6;">To celebrate your special day, enjoy a birthday treat on us — <strong>[offer, e.g. a complimentary add-on]</strong> with any service this month.</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="https://your-booking-link.example.com" style="background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Book Your Birthday Treat</a>
    </p>
</div>
HTML,
        ],
        [
            'name' => 'Festive greetings offer',
            'category' => 'Promotions',
            'subject' => 'Celebrate the season with a little pampering',
            'body_html' => <<<'HTML'
<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1e293b;">
    <h1 style="font-size:22px;">Happy Festivities, {{customer_name}}!</h1>
    <p style="font-size:15px;line-height:1.6;">This festive season, treat yourself to some well-deserved relaxation. Enjoy <strong>[offer, e.g. 15% off]</strong> on all treatments through [date].</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="https://your-booking-link.example.com" style="background:#db2777;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Claim This Offer</a>
    </p>
    <p style="font-size:13px;color:#64748b;">Wishing you and your loved ones a wonderful celebration.</p>
</div>
HTML,
        ],
    ];

    public function index(): Response
    {
        $campaigns = EmailCampaign::latest()->get()->map(fn (EmailCampaign $campaign) => [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'recipients_count' => $campaign->recipients_count,
            'open_rate' => $campaign->openRate(),
            'click_rate' => $campaign->clickRate(),
            'sent_at' => $campaign->sent_at,
        ]);

        return Inertia::render('EmailCampaigns/Index', ['campaigns' => $campaigns]);
    }

    public function create(): Response
    {
        return Inertia::render('EmailCampaigns/Create', ['starterTemplates' => self::STARTER_TEMPLATES]);
    }

    public function store(Request $request, CreateEmailCampaignAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $campaign = $action->execute([...$data, 'created_by_user_id' => $request->user()->id]);

        return redirect()->route('email-campaigns.show', $campaign)->with('success', 'Campaign saved as a draft.');
    }

    public function show(EmailCampaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        return Inertia::render('EmailCampaigns/Show', [
            'campaign' => $campaign,
            'recipients' => $campaign->recipients()->with('customer:id,name')->latest()->paginate(25),
        ]);
    }

    public function send(EmailCampaign $campaign, SendEmailCampaignAction $action): RedirectResponse
    {
        $this->authorize('view', $campaign);

        $action->execute($campaign);

        return back()->with('success', 'Campaign is sending now — this can take a few minutes for larger audiences.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('email-campaigns.index')->with('success', 'Draft deleted.');
    }

    public function audiencePreview(Request $request, BuildCampaignAudienceAction $action): JsonResponse
    {
        $filter = $request->validate([
            'type' => ['required', Rule::in(['all', 'vip', 'tag', 'inactive_days'])],
            'tag' => ['nullable', 'string', 'max:255'],
            'days' => ['nullable', 'integer', 'min:1', 'max:730'],
        ]);

        return response()->json(['count' => $action->count($filter)]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            // Raised from 50000 — a GrapesJS export with inline styles is considerably more
            // verbose than the hand-written starter templates.
            'body_html' => ['required', 'string', 'max:200000'],
            'audience_filter' => ['required', 'array'],
            'audience_filter.type' => ['required', Rule::in(['all', 'vip', 'tag', 'inactive_days'])],
            'audience_filter.tag' => ['nullable', 'string', 'max:255'],
            'audience_filter.days' => ['nullable', 'integer', 'min:1', 'max:730'],
        ]);

        return $data;
    }
}

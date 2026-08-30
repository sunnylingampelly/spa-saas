<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\WhatsApp\Actions\BuildWhatsAppAudienceAction;
use App\Domain\WhatsApp\Actions\CreateWhatsAppCampaignAction;
use App\Domain\WhatsApp\Actions\SendWhatsAppCampaignAction;
use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppCampaignController extends Controller
{
    public function index(): Response
    {
        $campaigns = WhatsAppCampaign::with('template:id,name')->latest()->get()->map(fn (WhatsAppCampaign $campaign) => [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'template_name' => $campaign->template->name,
            'status' => $campaign->status,
            'recipients_count' => $campaign->recipients_count,
            'delivery_rate' => $campaign->deliveryRate(),
            'read_rate' => $campaign->readRate(),
            'sent_at' => $campaign->sent_at,
        ]);

        return Inertia::render('WhatsAppCampaigns/Index', ['campaigns' => $campaigns]);
    }

    public function create(): Response
    {
        $templates = WhatsAppTemplate::where('status', 'approved')->orderBy('name')->get()
            ->map(fn (WhatsAppTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'header_text' => $template->header_text,
                'body_text' => $template->body_text,
                'footer_text' => $template->footer_text,
                'buttons' => $template->buttons,
                'variable_count' => $template->variableCount(),
            ]);

        return Inertia::render('WhatsAppCampaigns/Create', ['approvedTemplates' => $templates]);
    }

    public function store(Request $request, CreateWhatsAppCampaignAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $campaign = $action->execute([...$data, 'created_by_user_id' => $request->user()->id]);

        return redirect()->route('whatsapp-campaigns.show', $campaign)->with('success', 'Campaign saved as a draft.');
    }

    public function show(WhatsAppCampaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        return Inertia::render('WhatsAppCampaigns/Show', [
            'campaign' => $campaign->load('template:id,name'),
            'recipients' => $campaign->recipients()->with('customer:id,name')->latest()->paginate(25),
        ]);
    }

    public function send(WhatsAppCampaign $campaign, SendWhatsAppCampaignAction $action): RedirectResponse
    {
        $this->authorize('view', $campaign);

        $action->execute($campaign);

        return back()->with('success', 'Campaign is sending now — this can take a few minutes for larger audiences.');
    }

    public function destroy(WhatsAppCampaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('whatsapp-campaigns.index')->with('success', 'Draft deleted.');
    }

    public function audiencePreview(Request $request, BuildWhatsAppAudienceAction $action): JsonResponse
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
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Friendly pre-check only — CreateWhatsAppCampaignAction re-fetches this through
            // Eloquent (findOrFail), which is what actually enforces both tenant ownership and
            // approved status, since a raw exists() rule respects neither.
            'whatsapp_template_id' => ['required', 'integer', 'exists:whatsapp_templates,id'],
            'variable_values' => ['array'],
            'variable_values.*.source' => ['required_with:variable_values', Rule::in(['customer_name', 'static'])],
            'variable_values.*.value' => ['nullable', 'string', 'max:255'],
            'audience_filter' => ['required', 'array'],
            'audience_filter.type' => ['required', Rule::in(['all', 'vip', 'tag', 'inactive_days'])],
            'audience_filter.tag' => ['nullable', 'string', 'max:255'],
            'audience_filter.days' => ['nullable', 'integer', 'min:1', 'max:730'],
        ]);
    }
}

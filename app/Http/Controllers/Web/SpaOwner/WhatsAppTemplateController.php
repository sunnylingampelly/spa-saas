<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\WhatsApp\Actions\CreateWhatsAppTemplateAction;
use App\Domain\WhatsApp\Actions\SyncWhatsAppTemplatesAction;
use App\Domain\WhatsApp\Exceptions\WhatsAppApiException;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('WhatsAppTemplates/Index', [
            'templates' => WhatsAppTemplate::latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('WhatsAppTemplates/Create');
    }

    public function store(Request $request, TenantContext $tenantContext, CreateWhatsAppTemplateAction $action): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        if (! filled($spa->whatsapp_business_account_id)) {
            return back()->with('error', 'Connect your WhatsApp Business Account in Settings before creating templates.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/', Rule::unique('whatsapp_templates')->where('spa_id', $spa->id)],
            'category' => ['required', Rule::in(['marketing', 'utility'])],
            'language' => ['required', 'string', 'max:10'],
            'header_text' => ['nullable', 'string', 'max:60'],
            'body_text' => ['required', 'string', 'max:1024'],
            'footer_text' => ['nullable', 'string', 'max:60'],
            'buttons' => ['nullable', 'array', 'max:3'],
            'buttons.*.type' => ['required_with:buttons', Rule::in(['QUICK_REPLY', 'URL'])],
            'buttons.*.text' => ['required_with:buttons', 'string', 'max:25'],
            'buttons.*.url' => ['required_if:buttons.*.type,URL', 'nullable', 'url', 'max:2000'],
            'variable_samples' => ['nullable', 'array'],
            'variable_samples.*' => ['string', 'max:255'],
        ], [
            'name.regex' => 'Template name must be lowercase letters, numbers, and underscores only (e.g. festive_offer).',
        ]);

        try {
            $template = $action->execute($spa, $data);
        } catch (WhatsAppApiException $e) {
            return back()->withInput()->with('error', "Meta rejected this template: {$e->getMessage()}");
        }

        return redirect()->route('whatsapp-templates.index')->with('success', "Template submitted to Meta for approval (status: {$template->status}).");
    }

    public function sync(TenantContext $tenantContext, SyncWhatsAppTemplatesAction $action): RedirectResponse
    {
        $spa = $tenantContext->getCurrentSpa();

        try {
            $updated = $action->execute($spa);
        } catch (WhatsAppApiException $e) {
            return back()->with('error', "Couldn't sync templates from Meta: {$e->getMessage()}");
        }

        return back()->with('success', $updated > 0 ? "Updated {$updated} template(s) from Meta." : 'All templates are already up to date.');
    }

    public function destroy(WhatsAppTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('whatsapp-templates.index')->with('success', 'Template deleted.');
    }
}

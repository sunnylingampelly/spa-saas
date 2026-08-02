<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Services\Actions\CreateServiceAction;
use App\Domain\Services\Actions\DeleteServiceAction;
use App\Domain\Services\Actions\ExportServicesAction;
use App\Domain\Services\Actions\ImportServicesAction;
use App\Domain\Services\Actions\SeedSampleServiceCatalogAction;
use App\Domain\Services\Actions\ToggleServiceStatusAction;
use App\Domain\Services\Actions\UpdateServiceAction;
use App\Domain\Services\DTOs\CreateServiceData;
use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Services/Index', [
            'services' => Service::query()->with('category')->latest()->paginate(15),
            'categories' => ServiceCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Services/Create', [
            'categories' => ServiceCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, CreateServiceAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $action->execute(new CreateServiceData(
            name: $data['name'],
            durationMinutes: $data['duration_minutes'],
            price: $data['price'],
            serviceCategoryId: $data['service_category_id'] ?? null,
            description: $data['description'] ?? null,
            offerPrice: $data['offer_price'] ?? null,
            gstRate: $data['gst_rate'] ?? 18.00,
            hsnSacCode: $data['hsn_sac_code'] ?? null,
            commissionType: $data['commission_type'] ?? 'percentage',
            commissionValue: $data['commission_value'] ?? 0,
            colorHex: $data['color_hex'] ?? null,
            status: $data['status'] ?? 'active',
        ));

        return redirect()->route('services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);

        return Inertia::render('Services/Edit', [
            'service' => $service,
            'categories' => ServiceCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Service $service, UpdateServiceAction $action): RedirectResponse
    {
        $this->authorize('update', $service);

        $action->execute($service, $this->validated($request));

        return redirect()->route('services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service, DeleteServiceAction $action): RedirectResponse
    {
        $this->authorize('delete', $service);

        $action->execute($service);

        return back()->with('success', 'Service removed.');
    }

    public function toggleStatus(Service $service, ToggleServiceStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $service);

        $action->execute($service);

        return back()->with('success', 'Service status updated.');
    }

    public function seedSampleCatalog(SeedSampleServiceCatalogAction $action): RedirectResponse
    {
        $action->execute();

        return back()->with('success', 'Sample service catalog loaded.');
    }

    public function export(ExportServicesAction $action): StreamedResponse
    {
        return $action->execute();
    }

    public function importTemplate(ExportServicesAction $action): StreamedResponse
    {
        return $action->template();
    }

    public function import(Request $request, ImportServicesAction $action): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $result = $action->execute($request->file('file'));

        return back()
            ->with('success', "Imported {$result->importedCount} service(s).")
            ->with('import_errors', $result->rowErrors);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            ...ImportServicesAction::validationRules(),
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'color_hex' => ['nullable', 'string', 'max:7'],
        ]);
    }
}

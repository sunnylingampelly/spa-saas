<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Services\Actions\CreateServiceCategoryAction;
use App\Domain\Services\Actions\DeleteServiceCategoryAction;
use App\Domain\Services\Actions\UpdateServiceCategoryAction;
use App\Domain\Services\Models\ServiceCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function store(Request $request, CreateServiceCategoryAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $action->execute($data);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, ServiceCategory $serviceCategory, UpdateServiceCategoryAction $action): RedirectResponse
    {
        $this->authorize('update', $serviceCategory);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $action->execute($serviceCategory, $data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(ServiceCategory $serviceCategory, DeleteServiceCategoryAction $action): RedirectResponse
    {
        $this->authorize('delete', $serviceCategory);

        $action->execute($serviceCategory);

        return back()->with('success', 'Category removed.');
    }
}

<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;

class ToggleServiceStatusAction
{
    public function execute(Service $service): Service
    {
        $service->update(['status' => $service->status === 'active' ? 'inactive' : 'active']);

        return $service;
    }
}

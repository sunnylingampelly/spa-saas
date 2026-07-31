<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;

class DeleteServiceAction
{
    public function execute(Service $service): void
    {
        $service->delete();
    }
}

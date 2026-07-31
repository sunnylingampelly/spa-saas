<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;

class UpdateServiceAction
{
    public function execute(Service $service, array $data): Service
    {
        $service->update($data);

        return $service;
    }
}

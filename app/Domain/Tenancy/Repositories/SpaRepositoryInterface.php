<?php

namespace App\Domain\Tenancy\Repositories;

use App\Domain\Tenancy\Models\Spa;
use Illuminate\Database\Eloquent\Collection;

interface SpaRepositoryInterface
{
    public function findForUser(int $userId): Collection;

    public function findBySlug(string $slug): ?Spa;
}

<?php

namespace App\Domain\Tenancy\Repositories;

use App\Domain\Tenancy\Models\Spa;
use Illuminate\Database\Eloquent\Collection;

class EloquentSpaRepository implements SpaRepositoryInterface
{
    public function findForUser(int $userId): Collection
    {
        return Spa::withoutGlobalScopes()
            ->whereHas('users', fn ($query) => $query->where('users.id', $userId))
            ->get();
    }

    public function findBySlug(string $slug): ?Spa
    {
        return Spa::withoutGlobalScopes()->where('slug', $slug)->first();
    }
}

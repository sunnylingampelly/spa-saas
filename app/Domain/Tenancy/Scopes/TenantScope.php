<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantContext = app(TenantContext::class);

        if ($tenantContext->hasSpa()) {
            $builder->where($model->getQualifiedTenantColumn(), $tenantContext->getCurrentSpaId());
        }
    }
}

<?php

namespace App\Domain\Shared\Concerns;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\Services\TenantContext;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->{$model->getTenantColumn()}) && app(TenantContext::class)->hasSpa()) {
                $model->{$model->getTenantColumn()} = app(TenantContext::class)->getCurrentSpaId();
            }
        });
    }

    public function getTenantColumn(): string
    {
        return 'spa_id';
    }

    public function getQualifiedTenantColumn(): string
    {
        return $this->getTable().'.'.$this->getTenantColumn();
    }

    public function spa()
    {
        return $this->belongsTo(Spa::class);
    }
}

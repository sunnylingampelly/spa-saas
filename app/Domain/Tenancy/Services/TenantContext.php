<?php

namespace App\Domain\Tenancy\Services;

use App\Domain\Tenancy\Models\Spa;

class TenantContext
{
    private ?int $currentSpaId = null;

    private ?Spa $currentSpa = null;

    public function setCurrentSpaId(int $spaId): void
    {
        if ($this->currentSpaId !== $spaId) {
            $this->currentSpa = null;
        }

        $this->currentSpaId = $spaId;
    }

    public function getCurrentSpaId(): ?int
    {
        return $this->currentSpaId;
    }

    public function hasSpa(): bool
    {
        return $this->currentSpaId !== null;
    }

    public function getCurrentSpa(): ?Spa
    {
        if ($this->currentSpaId === null) {
            return null;
        }

        return $this->currentSpa ??= Spa::withoutGlobalScopes()->find($this->currentSpaId);
    }

    public function clear(): void
    {
        $this->currentSpaId = null;
        $this->currentSpa = null;
    }
}

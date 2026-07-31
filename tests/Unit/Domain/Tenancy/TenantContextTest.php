<?php

namespace Tests\Unit\Domain\Tenancy;

use App\Domain\Tenancy\Services\TenantContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_it_has_no_spa_by_default(): void
    {
        $context = new TenantContext;

        $this->assertFalse($context->hasSpa());
        $this->assertNull($context->getCurrentSpaId());
    }

    public function test_it_stores_the_current_spa_id(): void
    {
        $context = new TenantContext;
        $context->setCurrentSpaId(42);

        $this->assertTrue($context->hasSpa());
        $this->assertSame(42, $context->getCurrentSpaId());
    }

    public function test_clear_resets_the_context(): void
    {
        $context = new TenantContext;
        $context->setCurrentSpaId(42);
        $context->clear();

        $this->assertFalse($context->hasSpa());
        $this->assertNull($context->getCurrentSpaId());
    }
}

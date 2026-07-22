<?php

namespace Tests\Unit\LegacyMigration\Foundation\Reconciliation;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FoundationReconcileCommandTest extends TestCase
{
    #[Test]
    public function it_fails_closed_without_measurements(): void
    {
        $this->artisan('legacy-migration:foundation-reconcile')
            ->expectsOutputToContain('Blocked')
            ->assertFailed();
    }
}

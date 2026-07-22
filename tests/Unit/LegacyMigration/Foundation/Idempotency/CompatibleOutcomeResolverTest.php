<?php

namespace Tests\Unit\LegacyMigration\Foundation\Idempotency;

use App\Services\LegacyMigration\Foundation\Idempotency\CompatibleOutcomeResolver;
use App\Services\LegacyMigration\Foundation\Idempotency\IdempotencyException;
use App\Services\LegacyMigration\Foundation\Idempotency\IdempotencyKey;
use App\Services\LegacyMigration\Foundation\Idempotency\IdempotencyOutcomeLookup;
use App\Services\LegacyMigration\Foundation\Idempotency\IdempotencyOutcomeType;
use App\Services\LegacyMigration\Foundation\Idempotency\IdempotencyResolutionAction;
use App\Services\LegacyMigration\Foundation\Idempotency\StoredIdempotencyOutcome;
use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompatibleOutcomeResolverTest extends TestCase
{
    #[Test]
    public function missing_outcome_is_created_and_partial_compatible_outcome_is_resumed(): void
    {
        $key = $this->key();
        self::assertSame(
            IdempotencyResolutionAction::Create,
            (new CompatibleOutcomeResolver(new FakeOutcomeLookup))->resolve($key)->action,
        );

        $stored = new StoredIdempotencyOutcome($key, MigrationState::CoreCommitting, null, false);
        self::assertSame(
            IdempotencyResolutionAction::Resume,
            (new CompatibleOutcomeResolver(new FakeOutcomeLookup($stored)))->resolve($key)->action,
        );
    }

    #[Test]
    public function completed_compatible_outcome_is_reused_and_never_replayed(): void
    {
        $key = $this->key();
        $stored = new StoredIdempotencyOutcome($key, MigrationState::Completed, hash('sha256', 'outcome'), true);

        $resolution = (new CompatibleOutcomeResolver(new FakeOutcomeLookup($stored)))->resolve($key);

        self::assertSame(IdempotencyResolutionAction::ReuseCompleted, $resolution->action);
        self::assertSame($stored, $resolution->stored);
    }

    #[Test]
    public function terminal_completion_lag_repairs_metadata_only(): void
    {
        $key = $this->key();
        $stored = new StoredIdempotencyOutcome($key, MigrationState::ReconciliationPassed, hash('sha256', 'outcome'), true);

        self::assertSame(
            IdempotencyResolutionAction::RepairTerminalMetadata,
            (new CompatibleOutcomeResolver(new FakeOutcomeLookup($stored)))->resolve($key)->action,
        );
    }

    #[Test]
    public function conflicting_lineage_fails_closed(): void
    {
        $requested = $this->key();
        $conflict = new IdempotencyKey(
            $requested->domain,
            $requested->token,
            $requested->outcomeType,
            hash('sha256', 'different-input'),
            $requested->contractVersion,
            $requested->transformationVersion,
            $requested->canonicalizationVersion,
            $requested->hmacKeyVersion,
        );

        $this->expectException(IdempotencyException::class);
        (new CompatibleOutcomeResolver(new FakeOutcomeLookup(
            new StoredIdempotencyOutcome($conflict, MigrationState::Completed, hash('sha256', 'outcome'), true),
        )))->resolve($requested);
    }

    #[Test]
    public function it_exposes_exactly_the_thirteen_phase_2f_outcome_types(): void
    {
        self::assertSame(13, count(IdempotencyOutcomeType::cases()));
    }

    private function key(): IdempotencyKey
    {
        return new IdempotencyKey(
            'patient_core',
            hash('sha256', 'key'),
            IdempotencyOutcomeType::PatientCore,
            hash('sha256', 'input'),
            '2F.1.0',
            'patient-v1',
            'typed-v1',
            'key-v1',
        );
    }
}

final readonly class FakeOutcomeLookup implements IdempotencyOutcomeLookup
{
    public function __construct(private ?StoredIdempotencyOutcome $outcome = null) {}

    public function find(string $domain, string $token): ?StoredIdempotencyOutcome
    {
        return $this->outcome;
    }
}

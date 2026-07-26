<?php

namespace App\Data\Maternity;

/**
 * Phase 14R.5 — everything an Emergency or Admission maternity card needs,
 * prepared by a service in the controller layer.
 *
 * The Blade partials that consume this issue ZERO queries: they read only the
 * arrays and scalars below.
 */
final class OperationalMaternityViewModel
{
    /**
     * @param  list<array<string, mixed>>  $cards  read-only summary cards
     * @param  array<string, bool>  $actions  permission-resolved action map
     * @param  list<string>  $warnings
     * @param  array<string, mixed>|null  $returnContext
     * @param  array<string, mixed>|null  $requestState  admission-request state
     */
    public function __construct(
        public readonly bool $contextEnabled,
        public readonly bool $handoffsEnabled,
        public readonly string $status,
        public readonly string $resolutionSource,
        public readonly ?int $pregnancyProfileId = null,
        public readonly ?string $pregnancyProfileUrl = null,
        public readonly ?int $laborEpisodeId = null,
        public readonly ?string $laborEpisodeUrl = null,
        public readonly ?int $postnatalCaseId = null,
        public readonly array $cards = [],
        public readonly array $actions = [],
        public readonly array $warnings = [],
        public readonly ?array $returnContext = null,
        public readonly ?array $requestState = null,
        public readonly ?array $candidateProfiles = null,
    ) {}

    /** Flag off, or a source this card does not apply to: render nothing. */
    public static function disabled(): self
    {
        return new self(
            contextEnabled: false,
            handoffsEnabled: false,
            status: OperationalMaternityContext::STATUS_NONE,
            resolutionSource: OperationalMaternityContext::SOURCE_NONE,
        );
    }

    public function shouldRender(): bool
    {
        return $this->contextEnabled;
    }

    public function isLinked(): bool
    {
        return $this->status === OperationalMaternityContext::STATUS_RESOLVED
            && $this->resolutionSource === OperationalMaternityContext::SOURCE_EXPLICIT;
    }

    /** Context shown as a suggestion — never treated as linked. */
    public function isSuggested(): bool
    {
        return $this->status === OperationalMaternityContext::STATUS_SUGGESTED;
    }

    public function isAmbiguous(): bool
    {
        return $this->status === OperationalMaternityContext::STATUS_AMBIGUOUS;
    }

    public function hasContext(): bool
    {
        return $this->pregnancyProfileId !== null;
    }

    public function can(string $action): bool
    {
        return (bool) ($this->actions[$action] ?? false);
    }
}

<?php

namespace App\Data\Consultation\Maternity;

/**
 * Phase 14R.6 — typed result of ADVISORY maternity readiness.
 *
 * There is deliberately no "blocked" state. The closed status set is
 * ready / warning / unavailable, and none of them prevents consultation
 * completion in this phase.
 *
 * Warnings are carried as localisation KEYS, never rendered text, so the same
 * result can be displayed in any locale and compared in tests without matching
 * on translations.
 */
final class ConsultationMaternityReadinessResult
{
    public const STATUS_READY = 'ready';
    public const STATUS_WARNING = 'warning';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public const MODE_GENERAL = 'general';
    public const MODE_ANTENATAL_REVIEW = 'antenatal_review';
    public const MODE_LABOR_REVIEW = 'labor_review';
    public const MODE_POSTNATAL_REVIEW = 'postnatal_review';

    /**
     * @param  list<string>  $warningCodes
     */
    private function __construct(
        public readonly string $status,
        public readonly string $mode,
        public readonly array $warningCodes = [],
    ) {}

    public static function ready(string $mode): self
    {
        return new self(self::STATUS_READY, $mode);
    }

    /** @param list<string> $warningCodes */
    public static function warning(string $mode, array $warningCodes): self
    {
        return new self(self::STATUS_WARNING, $mode, array_values(array_unique($warningCodes)));
    }

    /** Flag off, wrong specialty, or no permission: render nothing. */
    public static function unavailable(): self
    {
        return new self(self::STATUS_UNAVAILABLE, self::MODE_GENERAL);
    }

    public function shouldRender(): bool
    {
        return $this->status !== self::STATUS_UNAVAILABLE;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function hasWarnings(): bool
    {
        return $this->warningCodes !== [];
    }

    /**
     * Advisory readiness NEVER blocks completion in this phase. Exposed
     * explicitly so callers read the intent rather than infer it from status.
     */
    public function blocksCompletion(): bool
    {
        return false;
    }

    public function modeLabel(): string
    {
        return __('consultation_maternity_summary.readiness.modes.'.$this->mode);
    }

    /** @return list<string> localised warning messages, resolved at render time */
    public function warnings(): array
    {
        return array_map(
            fn (string $code) => __('consultation_maternity_summary.readiness.warnings.'.$code),
            $this->warningCodes
        );
    }

    /** @return array<string, mixed> identifier-only shape for tests/logging */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'mode' => $this->mode,
            'warning_codes' => $this->warningCodes,
            'blocks_completion' => false,
        ];
    }
}

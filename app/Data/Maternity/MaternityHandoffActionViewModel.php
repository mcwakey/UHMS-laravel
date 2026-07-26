<?php

namespace App\Data\Maternity;

use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5.1 — the single typed description of one handoff action.
 *
 * Blade renders FROM this model and does nothing else: it must never
 * rediscover permissions, query for profiles/requests/admissions/emergency
 * cases, infer whether a record should be reused, build a return URL, or
 * interpret an ambiguous legacy `source_id`. Every one of those decisions is
 * made server-side and arrives here already resolved.
 *
 * `state` is a closed set. A trigger is only clickable when the state is
 * ENABLED or EXISTING_RECORD; every other state renders an honest reason
 * instead of a button the server would inevitably reject.
 */
final class MaternityHandoffActionViewModel
{
    /* ── States (closed set) ───────────────────────────────────────────── */

    /** The clinician may execute this now. */
    public const STATE_ENABLED = 'enabled';

    /** The target already exists — open it rather than creating a duplicate. */
    public const STATE_EXISTING_RECORD = 'existing_record';

    /** Lifecycle forbids it (e.g. a completed consultation). */
    public const STATE_BLOCKED = 'blocked';

    /** Not offered in this workspace / no configuration for it. */
    public const STATE_UNAVAILABLE = 'unavailable';

    /** Several candidate profiles — the clinician must choose explicitly. */
    public const STATE_AMBIGUOUS = 'ambiguous';

    /** A bridge or target-domain permission is missing. */
    public const STATE_PERMISSION_MISSING = 'permission_missing';

    /** The integration feature flag is off. */
    public const STATE_FEATURE_DISABLED = 'feature_disabled';

    /** No explicit pregnancy context to act on. */
    public const STATE_INVALID_CONTEXT = 'invalid_context';

    /** Confirmation weight the dialog should carry. */
    public const CONFIRM_NONE = 'none';
    public const CONFIRM_SIMPLE = 'simple';
    public const CONFIRM_REASON = 'reason';

    /**
     * @param  array<string, mixed>  $routeParameters
     * @param  list<string>  $requiredFields
     * @param  array<string, mixed>|null  $existingRecord  ['id','label','status','url']
     * @param  array<string, mixed>  $context  read-only display data for the modal body
     */
    public function __construct(
        public readonly string $actionKey,
        public readonly string $label,
        public readonly string $state,
        public readonly bool $visible = true,
        public readonly ?string $description = null,
        public readonly ?string $disabledReason = null,
        public readonly ?string $routeName = null,
        public readonly array $routeParameters = [],
        public readonly string $method = 'POST',
        public readonly ?string $modalId = null,
        public readonly string $confirmation = self::CONFIRM_SIMPLE,
        public readonly array $requiredFields = [],
        public readonly ?string $sourceModule = null,
        public readonly mixed $sourceRecordId = null,
        public readonly ?string $targetModule = null,
        public readonly ?string $targetContextType = null,
        public readonly ?array $existingRecord = null,
        public readonly ?MaternityReturnContext $returnContext = null,
        public readonly array $context = [],
        public readonly ?string $fallbackUrl = null,
        public readonly ?string $fallbackLabel = null,
    ) {}

    /** A permission-missing action: rendered as a reason, never as a form. */
    public static function permissionMissing(string $actionKey, string $label): self
    {
        return new self(
            actionKey: $actionKey,
            label: $label,
            state: self::STATE_PERMISSION_MISSING,
            visible: false,
            disabledReason: __('maternity_handoffs.states.permission_required'),
        );
    }

    public static function featureDisabled(string $actionKey, string $label): self
    {
        return new self(
            actionKey: $actionKey,
            label: $label,
            state: self::STATE_FEATURE_DISABLED,
            visible: false,
            disabledReason: __('maternity_handoffs.states.feature_disabled'),
        );
    }

    /** True when the trigger should open a modal and submit. */
    public function isExecutable(): bool
    {
        return $this->visible
            && in_array($this->state, [self::STATE_ENABLED, self::STATE_EXISTING_RECORD], true)
            && $this->modalId !== null
            && $this->routeName !== null;
    }

    /** True when the UI should point at an existing record instead of creating. */
    public function reusesExistingRecord(): bool
    {
        return $this->state === self::STATE_EXISTING_RECORD && $this->existingRecord !== null;
    }

    /** True when a visible, non-executable explanation should be shown. */
    public function showsReason(): bool
    {
        return $this->visible && ! $this->isExecutable() && filled($this->disabledReason);
    }

    public function requiresReason(): bool
    {
        return $this->confirmation === self::CONFIRM_REASON;
    }

    public function url(): ?string
    {
        if ($this->routeName === null) {
            return null;
        }

        try {
            return route($this->routeName, $this->routeParameters);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Hidden return-context fields, already validated as an internal named
     * route. A raw URL can never appear here.
     *
     * @return array<string, string>
     */
    public function returnFields(): array
    {
        return $this->returnContext?->toFormFields() ?? [];
    }

    /**
     * Identifier-only shape for tests and debugging. No clinical content.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action_key' => $this->actionKey,
            'state' => $this->state,
            'visible' => $this->visible,
            'executable' => $this->isExecutable(),
            'modal_id' => $this->modalId,
            'route_name' => $this->routeName,
            'method' => $this->method,
            'confirmation' => $this->confirmation,
            'required_fields' => $this->requiredFields,
            'source_module' => $this->sourceModule,
            'source_record_id' => $this->sourceRecordId,
            'target_module' => $this->targetModule,
            'target_context_type' => $this->targetContextType,
            'existing_record_id' => $this->existingRecord['id'] ?? null,
        ];
    }
}

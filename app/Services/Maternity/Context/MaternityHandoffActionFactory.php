<?php

namespace App\Services\Maternity\Context;

use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5.1 — builds typed handoff actions.
 *
 * Centralises the state ladder so every module resolves availability the same
 * way, in the same order, with the same wording:
 *
 *   feature flag → permissions → lifecycle → context → existing record → enabled
 *
 * The order matters. A clinician missing a permission should be told that, not
 * "link a profile first"; a completed consultation should say so rather than
 * offering a form the server will reject.
 */
class MaternityHandoffActionFactory
{
    /**
     * Resolve one action through the ladder.
     *
     * @param  array{
     *     action: string,
     *     label: string,
     *     description?: ?string,
     *     feature_enabled: bool,
     *     has_permission: bool,
     *     lifecycle_ok?: bool,
     *     lifecycle_reason?: ?string,
     *     context_ok?: bool,
     *     context_reason?: ?string,
     *     ambiguous?: bool,
     *     existing?: ?array<string, mixed>,
     *     existing_label?: ?string,
     *     route?: ?string,
     *     parameters?: array<string, mixed>,
     *     method?: string,
     *     modal?: ?string,
     *     confirmation?: string,
     *     required_fields?: list<string>,
     *     source_module?: ?string,
     *     source_record_id?: mixed,
     *     target_module?: ?string,
     *     target_context_type?: ?string,
     *     context?: array<string, mixed>,
     *     fallback_url?: ?string,
     *     fallback_label?: ?string,
     *     unavailable_reason?: ?string,
     *  }  $spec
     */
    public function make(array $spec, ?MaternityReturnContext $returnContext = null): Action
    {
        $base = [
            'actionKey' => $spec['action'],
            'label' => $spec['label'],
            'description' => $spec['description'] ?? null,
            'routeName' => $spec['route'] ?? null,
            'routeParameters' => $spec['parameters'] ?? [],
            'method' => $spec['method'] ?? 'POST',
            'modalId' => $spec['modal'] ?? null,
            'confirmation' => $spec['confirmation'] ?? Action::CONFIRM_SIMPLE,
            'requiredFields' => $spec['required_fields'] ?? [],
            'sourceModule' => $spec['source_module'] ?? null,
            'sourceRecordId' => $spec['source_record_id'] ?? null,
            'targetModule' => $spec['target_module'] ?? 'maternity',
            'targetContextType' => $spec['target_context_type'] ?? null,
            'returnContext' => $returnContext,
            'context' => $spec['context'] ?? [],
            'fallbackUrl' => $spec['fallback_url'] ?? null,
            'fallbackLabel' => $spec['fallback_label'] ?? null,
        ];

        // 1. Feature flag — hidden entirely, no modal body, no queries.
        if (! $spec['feature_enabled']) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_FEATURE_DISABLED,
                'visible' => false,
                'disabledReason' => __('maternity_handoffs.states.feature_disabled'),
            ]));
        }

        // 2. Permissions — the user never sees a form for something they cannot
        //    execute. Hidden rather than shown-disabled, so the UI does not
        //    advertise capabilities the role does not have.
        if (! $spec['has_permission']) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_PERMISSION_MISSING,
                'visible' => false,
                'disabledReason' => __('maternity_handoffs.states.permission_required'),
            ]));
        }

        // 3. Explicitly configured as unavailable (e.g. no Obstetrics mapping).
        //    Visible WITH a reason and, where one exists, a real fallback link.
        if (filled($spec['unavailable_reason'] ?? null)) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_UNAVAILABLE,
                'visible' => true,
                'disabledReason' => $spec['unavailable_reason'],
            ]));
        }

        // 4. Lifecycle (completed consultation, disposed emergency case, …).
        if (($spec['lifecycle_ok'] ?? true) === false) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_BLOCKED,
                'visible' => true,
                'disabledReason' => $spec['lifecycle_reason']
                    ?? __('maternity_handoffs.states.blocked_lifecycle'),
            ]));
        }

        // 5. Ambiguity is reported before "no context": several candidates is a
        //    different problem from none, and needs a different instruction.
        if ($spec['ambiguous'] ?? false) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_AMBIGUOUS,
                'visible' => true,
                'disabledReason' => __('maternity_handoffs.states.ambiguous_context'),
            ]));
        }

        // 6. Context.
        if (($spec['context_ok'] ?? true) === false) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_INVALID_CONTEXT,
                'visible' => true,
                'disabledReason' => $spec['context_reason']
                    ?? __('maternity_handoffs.states.invalid_context'),
            ]));
        }

        // 7. An existing record — surfaced as "open it", never as a second
        //    create button. The server is still idempotent regardless.
        if (! empty($spec['existing'])) {
            return new Action(...array_merge($base, [
                'state' => Action::STATE_EXISTING_RECORD,
                'visible' => true,
                'label' => $spec['existing_label'] ?? $spec['label'],
                'existingRecord' => $spec['existing'],
            ]));
        }

        return new Action(...array_merge($base, [
            'state' => Action::STATE_ENABLED,
            'visible' => true,
        ]));
    }

    /**
     * Convenience: an action that exists in another workspace only.
     */
    public function unavailable(string $action, string $label, string $reason): Action
    {
        return new Action(
            actionKey: $action,
            label: $label,
            state: Action::STATE_UNAVAILABLE,
            visible: false,
            disabledReason: $reason,
        );
    }
}

<?php

namespace App\Services\Maternity\Handoffs;

use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Models\User;
use App\Services\Maternity\Context\MaternityHandoffActionFactory;
use App\Support\Maternity\MaternityReturnContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5.1 — prepares the explicit "Create/Open Emergency Case" action for
 * a Labor Episode, Delivery Record or Postnatal Case.
 *
 * Built once per maternity show page. Returns an empty array — with ZERO
 * queries — while MATERNITY_EMERGENCY_HANDOFFS_ENABLED is off.
 *
 * The escalation FLAG on the source record is only ever DISPLAYED here; it
 * never triggers anything. Only the clinician confirming this action creates or
 * opens an Emergency Case, and that goes through EmergencyCaseService.
 */
class MaternityEmergencyHandoffPresenter
{
    public function __construct(
        private readonly MaternityEmergencyHandoffService $handoffs,
        private readonly MaternityHandoffActionFactory $actions,
    ) {}

    /**
     * @return array<string, Action>
     */
    public function build(Model $source, ?User $user): array
    {
        if (! $user || ! $this->handoffs->enabled() || ! $this->handoffs->supports($source)) {
            return [];
        }

        [$routeName, $parameters, $returnRoute] = $this->routing($source);

        if (! $routeName) {
            return [];
        }

        // Only looked up once the feature is on — never on a dark page.
        $existing = $this->handoffs->existingLinkedCase($source)
            ?? $this->handoffs->existingCaseOnVisit($source);

        $return = MaternityReturnContext::make(
            MaternityReturnContext::MODULE_MATERNITY,
            $returnRoute,
            $parameters,
            'maternity-emergency-handoff',
        );

        return [
            'emergency_handoff' => $this->actions->make([
                'action' => 'emergency_handoff',
                'label' => __('maternity_handoffs.escalation.create_handoff'),
                'existing_label' => __('maternity_handoffs.escalation.open_existing_case'),
                'description' => __('maternity_handoffs.escalation.case_will_be_created'),
                'feature_enabled' => true,
                'has_permission' => $user->can('maternity.emergency_handoff.create')
                    && $user->can('emergency.case.create'),
                'lifecycle_ok' => true,
                'existing' => $existing ? [
                    'id' => $existing->id,
                    'label' => $existing->emergency_number ?: ('#'.$existing->id),
                    'status' => $existing->emergency_status,
                    'url' => $this->route('admin.emergency.cases.show', $existing),
                ] : null,
                'route' => $routeName,
                'parameters' => $parameters,
                'modal' => 'maternityEmergencyHandoffModal',
                'source_module' => 'maternity',
                'source_record_id' => $source->getKey(),
                'target_module' => 'emergency',
                'target_context_type' => $this->contextType($source),
                'context' => [
                    'summary' => $this->summary($source),
                    'notices' => [
                        __('maternity_handoffs.escalation.source_record_preserved'),
                    ],
                ],
            ], $return),
        ];
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    /**
     * @return array{0: ?string, 1: array<string, mixed>, 2: string}
     */
    private function routing(Model $source): array
    {
        return match (true) {
            $source instanceof LaborEpisode => [
                'admin.maternity.labor.emergency-handoff',
                ['laborEpisode' => $source->getKey()],
                'admin.maternity.labor.show',
            ],
            $source instanceof DeliveryRecord => [
                'admin.maternity.deliveries.emergency-handoff',
                ['deliveryRecord' => $source->getKey()],
                'admin.maternity.deliveries.show',
            ],
            $source instanceof PostnatalCase => [
                'admin.maternity.postnatal.emergency-handoff',
                ['postnatalCase' => $source->getKey()],
                'admin.maternity.postnatal.show',
            ],
            default => [null, [], ''],
        };
    }

    private function contextType(Model $source): string
    {
        return match (true) {
            $source instanceof LaborEpisode => 'labor',
            $source instanceof DeliveryRecord => 'delivery',
            $source instanceof PostnatalCase => 'postnatal',
            default => 'pregnancy_profile',
        };
    }

    /**
     * Read-only display values — identifiers, status and the escalation flag.
     * No clinical measurement or observation is shown.
     *
     * @return array<string, ?string>
     */
    private function summary(Model $source): array
    {
        $flag = match (true) {
            $source instanceof LaborEpisode => $source->emergency_escalation_required
                ? __('maternity_handoffs.fields.emergency_escalation_flagged')
                : null,
            $source instanceof PostnatalCase => $source->referral_required
                ? __('maternity_handoffs.fields.referral_required')
                : null,
            default => null,
        };

        return array_filter([
            __('maternity_handoffs.ownership.maternity_longitudinal_record') => class_basename($source)
                .' #'.$source->getKey(),
            __('maternity_handoffs.cards.pregnancy') => $source->pregnancy_profile_id
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.pregnancy'),
                    'id' => $source->pregnancy_profile_id,
                ])
                : null,
            __('maternity_handoffs.fields.escalation') => $flag,
        ], fn ($value) => $value !== null);
    }

    private function route(string $name, mixed $parameter): ?string
    {
        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}

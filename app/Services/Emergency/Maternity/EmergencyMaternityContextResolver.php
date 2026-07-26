<?php

namespace App\Services\Emergency\Maternity;

use App\Data\Maternity\OperationalMaternityContext;
use App\Models\EmergencyCase;
use App\Services\Maternity\Context\AbstractOperationalMaternityContextResolver;
use App\Services\Maternity\Context\MaternityContextTargetService;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — resolve the maternity context for an Emergency case.
 *
 * Resolution order:
 *   1. explicit active Emergency ↔ Maternity links     → RESOLVED
 *   2. maternity records on the SAME VISIT             → SUGGESTED (never persisted)
 *   3. none / ambiguous
 *
 * Step 2 is deliberately weaker than the consultation resolver's: it produces a
 * SUGGESTED context that the UI must label as a suggestion and that no code
 * path may persist without an explicit clinician confirmation. There is no
 * "single active pregnancy profile" fallback for Emergency — an unrelated
 * pregnancy must never colour an acute presentation.
 *
 * This resolver creates NOTHING: no Emergency case, no Labor episode, no
 * pregnancy profile, no link.
 */
class EmergencyMaternityContextResolver extends AbstractOperationalMaternityContextResolver
{
    /** @var array<int, OperationalMaternityContext> request-scoped memo */
    private array $memo = [];

    public function __construct(
        EmergencyMaternityLinkService $links,
        MaternityContextTargetService $targets,
    ) {
        parent::__construct($links, $targets);
    }

    /** Explicit links only — used by write paths that must not act on a guess. */
    public function resolveExplicitOnly(EmergencyCase $case): OperationalMaternityContext
    {
        return $this->fromExplicitLinks($case) ?? OperationalMaternityContext::none($case);
    }

    /** Full resolution, memoised so a request resolves at most once per case. */
    public function resolve(EmergencyCase $case): OperationalMaternityContext
    {
        return $this->memo[$case->id] ??= $this->doResolve($case);
    }

    private function doResolve(EmergencyCase $case): OperationalMaternityContext
    {
        $explicit = $this->fromExplicitLinks($case);

        if ($explicit) {
            return $explicit;
        }

        // Fast path: no pregnancy profile for this patient → nothing to suggest.
        if (! $this->patientHasAnyPregnancyProfile($case)) {
            return OperationalMaternityContext::none($case);
        }

        return $this->suggestedFromVisit($case) ?? OperationalMaternityContext::none($case);
    }

    /**
     * Same-visit maternity records, surfaced as a SUGGESTION only.
     */
    private function suggestedFromVisit(EmergencyCase $case): ?OperationalMaternityContext
    {
        if (! $case->visit_id) {
            return null;
        }

        return $this->resolveFromCandidates(
            $case,
            $this->profileIdsFrom('visit_id', (int) $case->visit_id),
            OperationalMaternityContext::SOURCE_VISIT,
            OperationalMaternityContext::STATUS_SUGGESTED,
            [__('maternity_handoffs.emergency.suggested_context_notice')],
        );
    }

    protected function patientId(Model $source): ?int
    {
        return $source->patient_id === null ? null : (int) $source->patient_id;
    }
}

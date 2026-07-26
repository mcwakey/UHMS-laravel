<?php

namespace App\Services\Emergency\Maternity;

use App\Enums\LogModule;
use App\Models\EmergencyMaternityLink;
use App\Services\Maternity\Context\AbstractMaternityLinkService;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — the only supported way to create, replace or retire an
 * Emergency ↔ Maternity link.
 *
 * Emergency never acquires maternity context implicitly: nothing here is called
 * from context resolution, a pregnancy-related complaint, an obstetric
 * diagnosis or a positive pregnancy test. Every row is an explicit clinician
 * action or a known idempotent handoff path.
 */
class EmergencyMaternityLinkService extends AbstractMaternityLinkService
{
    protected function linkModel(): string
    {
        return EmergencyMaternityLink::class;
    }

    protected function sourceColumn(): string
    {
        return 'emergency_case_id';
    }

    protected function sourcePatientId(Model $source): ?int
    {
        return $source->patient_id === null ? null : (int) $source->patient_id;
    }

    protected function logModule(): LogModule
    {
        return LogModule::EMERGENCY;
    }

    protected function eventPrefix(): string
    {
        return 'EMERGENCY_MATERNITY_CONTEXT';
    }

    protected function logContext(Model $source): array
    {
        return array_filter([
            'emergency_case_id' => $source->id,
            'patient_id' => $source->patient_id,
            'visit_id' => $source->visit_id,
        ], fn ($value) => $value !== null);
    }
}

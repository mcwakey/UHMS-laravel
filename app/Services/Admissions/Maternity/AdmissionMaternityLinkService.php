<?php

namespace App\Services\Admissions\Maternity;

use App\Enums\LogModule;
use App\Models\AdmissionMaternityLink;
use App\Services\Maternity\Context\AbstractMaternityLinkService;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — the only supported way to create, replace or retire an
 * Admission ↔ Maternity link.
 *
 * An admission never acquires maternity context merely because the patient is
 * admitted: rows come from explicit clinician linking or from Admission Request
 * context propagation on conversion.
 */
class AdmissionMaternityLinkService extends AbstractMaternityLinkService
{
    /** Set by the propagation service so the audit row records its origin. */
    public const META_SOURCE_REQUEST = 'source_admission_request_id';

    protected function linkModel(): string
    {
        return AdmissionMaternityLink::class;
    }

    protected function sourceColumn(): string
    {
        return 'admission_id';
    }

    protected function sourcePatientId(Model $source): ?int
    {
        return $source->patient_id === null ? null : (int) $source->patient_id;
    }

    protected function logModule(): LogModule
    {
        return LogModule::ADMISSION;
    }

    protected function eventPrefix(): string
    {
        return 'ADMISSION_MATERNITY_CONTEXT';
    }

    protected function logContext(Model $source): array
    {
        return array_filter([
            'admission_id' => $source->id,
            'patient_id' => $source->patient_id,
            'visit_id' => $source->visit_id,
        ], fn ($value) => $value !== null);
    }

    /**
     * Persist the originating admission request on the link row when the
     * context was propagated rather than hand-linked. Audit only — resolution
     * never reads it to infer context.
     */
    protected function extraColumns(Model $source, array $metadata): array
    {
        $requestId = $metadata[self::META_SOURCE_REQUEST] ?? null;

        return $requestId === null ? [] : ['admission_request_id' => (int) $requestId];
    }
}

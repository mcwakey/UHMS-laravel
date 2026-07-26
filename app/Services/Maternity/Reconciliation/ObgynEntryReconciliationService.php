<?php

namespace App\Services\Maternity\Reconciliation;

use App\Enums\ConsultationMaternityContextType;
use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Collection;

/**
 * Phase 14R.6 — READ-ONLY classification of historical O&G specialty entries.
 *
 * Every method is a pure read. This service creates no link, no pregnancy
 * profile, no ANC visit and no activity log, and it never modifies a specialty
 * entry. The original entry is always preserved — the output is a
 * recommendation for a FUTURE phase, not an action.
 *
 * Determinism matters: the same database state must produce the same report, so
 * ordering is fully specified and no `latest()`-without-tiebreak is used.
 */
class ObgynEntryReconciliationService
{
    public const SAFE_TO_LINK = 'safe_to_link';
    public const SAFE_TO_MIGRATE = 'safe_to_migrate';
    public const CONFLICT_REQUIRES_REVIEW = 'conflict_requires_review';
    public const HISTORICAL_ONLY = 'historical_only';
    public const INSUFFICIENT_CONTEXT = 'insufficient_context';

    /**
     * Maternity-owned or partially-owned sections, from the approved
     * source-of-truth matrix. Consultation-owned sections are deliberately
     * absent — `menstrual_history`, `previous_complications`, `action_plan`,
     * `current_complaints`, `high_risk_notes`, `booking_status`,
     * `planned_place`, `delivery_plan` and every ordinary Gynaecology section
     * are never migration candidates.
     *
     * @var array<string, list<string>> profile code => section keys
     */
    public const SCOPE = [
        'obstetrics' => [
            'obstetric_history',
            'lmp_edd_gestational_age',
            'current_pregnancy',
            'antenatal_vitals',
            'fetal_assessment',
            'risk_assessment',
            'birth_plan',
            // Hidden legacy sections: audited when present, never changed.
            'lab_screening',
            'ultrasound_findings',
        ],
        'gynecology' => [
            'obstetric_history',
        ],
    ];

    public function __construct(
        private readonly ObgynEntryValueParser $parser,
    ) {}

    /**
     * Classify every in-scope entry.
     *
     * @param  array{patient?: ?int, consultation?: ?int, profile?: ?int, limit?: ?int, include_historical?: bool}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function scan(array $filters = []): Collection
    {
        $sections = collect(self::SCOPE)->flatten()->unique()->values()->all();

        $query = ConsultationSpecialtyEntry::query()
            ->whereIn('section_key', $sections)
            ->with(['profile:id,code,name'])
            // `consultation_specialty_entries` carries no patient_id — the
            // patient is reached through the consultation route.
            ->when($filters['patient'] ?? null, fn ($q, $id) => $q->whereIn(
                'consultation_id',
                VisitConsultationRoute::query()->where('patient_id', $id)->select('id')
            ))
            ->when($filters['consultation'] ?? null, fn ($q, $id) => $q->where('consultation_id', $id))
            // Deterministic ordering — the report must be reproducible.
            ->orderBy('consultation_id')
            ->orderBy('section_key')
            ->orderBy('id');

        if ($limit = ($filters['limit'] ?? null)) {
            $query->limit((int) $limit);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            return collect();
        }

        $routes = VisitConsultationRoute::query()
            ->whereIn('id', $entries->pluck('consultation_id')->unique()->filter())
            ->get(['id', 'patient_id', 'visit_id', 'status', 'completed_at'])
            ->keyBy('id');

        return $entries
            ->map(fn (ConsultationSpecialtyEntry $entry) => $this->classify(
                $entry,
                $routes->get($entry->consultation_id),
                $filters['profile'] ?? null,
            ))
            ->filter()
            ->values();
    }

    /**
     * Aggregate counts by classification, for the default console output.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public function summarise(Collection $rows): array
    {
        $counts = array_fill_keys([
            self::SAFE_TO_LINK, self::SAFE_TO_MIGRATE, self::CONFLICT_REQUIRES_REVIEW,
            self::HISTORICAL_ONLY, self::INSUFFICIENT_CONTEXT,
        ], 0);

        foreach ($rows as $row) {
            $counts[$row['classification']] = ($counts[$row['classification']] ?? 0) + 1;
        }

        return $counts;
    }

    /* ── Classification ────────────────────────────────────────────────── */

    /**
     * @return array<string, mixed>|null
     */
    private function classify(
        ConsultationSpecialtyEntry $entry,
        ?VisitConsultationRoute $route,
        ?int $profileFilter,
    ): ?array {
        $profileCode = $entry->profile?->code;
        $sectionKey = (string) $entry->section_key;

        // A section is only in scope for the profile that owns it: Gynaecology
        // contributes obstetric_history alone.
        if ($profileCode && ! in_array($sectionKey, self::SCOPE[$profileCode] ?? [], true)) {
            return null;
        }

        $values = is_array($entry->entry) ? $entry->entry : [];
        $linkedProfileId = $this->linkedPregnancyProfileId($entry->consultation_id);

        if ($profileFilter && (int) $profileFilter !== (int) $linkedProfileId) {
            return null;
        }

        $base = [
            'consultation_route_id' => (int) $entry->consultation_id,
            'patient_id' => $route?->patient_id ? (int) $route->patient_id : null,
            'specialty_profile' => $profileCode,
            'section_key' => $sectionKey,
            'entry_id' => (int) $entry->id,
            'entry_recorded_at' => $entry->created_at?->toIso8601String(),
            'linked_pregnancy_profile_id' => $linkedProfileId,
            'candidate_target_type' => null,
            'candidate_target_id' => null,
            'field_comparisons' => [],
            'parser_warnings' => [],
            // Stable hash of the ORIGINAL entry JSON, so a reviewer can prove
            // the source has not changed between runs.
            'entry_hash' => hash('sha256', json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'),
        ];

        $patientId = $base['patient_id'];

        if (! $patientId || $values === []) {
            return $base + [
                'classification' => self::INSUFFICIENT_CONTEXT,
                'recommended_action' => 'review_manually',
                'reason_code' => $values === [] ? 'empty_entry' : 'patient_not_identifiable',
            ];
        }

        $profile = $linkedProfileId
            ? PregnancyProfile::query()->find($linkedProfileId)
            : $this->soleProfileForPatient($patientId);

        if (! $profile) {
            $isCompleted = $route?->status === VisitConsultationRoute::STATUS_COMPLETED;

            return $base + [
                'classification' => $isCompleted ? self::HISTORICAL_ONLY : self::INSUFFICIENT_CONTEXT,
                'recommended_action' => 'preserve_as_encounter_history',
                'reason_code' => $isCompleted ? 'completed_without_profile' : 'no_identifiable_profile',
            ];
        }

        $base['candidate_target_type'] = 'pregnancy_profile';
        $base['candidate_target_id'] = (int) $profile->id;

        $comparison = $this->compareSection($sectionKey, $values, $profile);
        $base['field_comparisons'] = $comparison['fields'];
        $base['parser_warnings'] = $comparison['warnings'];

        if ($comparison['conflicts'] !== []) {
            return $base + [
                'classification' => self::CONFLICT_REQUIRES_REVIEW,
                'recommended_action' => 'review_manually',
                'reason_code' => $comparison['conflicts'][0],
            ];
        }

        if ($comparison['unparseable']) {
            return $base + [
                'classification' => self::CONFLICT_REQUIRES_REVIEW,
                'recommended_action' => 'review_manually',
                'reason_code' => 'unparseable_value',
            ];
        }

        // A matching target already exists → a bridge link is all that is
        // needed; the entry itself is preserved either way.
        if ($comparison['has_target']) {
            return $base + [
                'classification' => self::SAFE_TO_LINK,
                'recommended_action' => 'create_bridge_link_only',
                'reason_code' => 'values_match_existing_record',
            ];
        }

        return $base + [
            'classification' => self::SAFE_TO_MIGRATE,
            'recommended_action' => 'create_target_record_then_link',
            'reason_code' => 'no_existing_target',
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{fields: array<string, mixed>, warnings: list<string>, conflicts: list<string>, unparseable: bool, has_target: bool}
     */
    private function compareSection(string $sectionKey, array $values, PregnancyProfile $profile): array
    {
        $fields = [];
        $warnings = [];
        $conflicts = [];
        $unparseable = false;
        $hasTarget = false;

        $record = function (string $field, array $parsed, mixed $profileValue = null) use (&$fields, &$warnings, &$unparseable, &$conflicts) {
            $fields[$field] = [
                'consultation_value' => $parsed['value'],
                'profile_value' => $profileValue,
                'confidence' => $parsed['confidence'],
            ];

            $warnings = array_merge($warnings, $parsed['warnings']);

            if ($parsed['confidence'] === ObgynEntryValueParser::CONFIDENCE_UNPARSEABLE) {
                $unparseable = true;

                return;
            }

            if ($profileValue !== null && $parsed['value'] !== null && $parsed['value'] != $profileValue) {
                $conflicts[] = $field.'_mismatch';
            }
        };

        switch ($sectionKey) {
            case 'obstetric_history':
                foreach (['gravida', 'para', 'abortions', 'living_children'] as $field) {
                    if (array_key_exists($field, $values)) {
                        $record($field, $this->parser->count($values[$field]), $profile->{$field});
                    }
                }
                $hasTarget = true; // the profile IS the target for this section
                break;

            case 'lmp_edd_gestational_age':
                if (array_key_exists('lmp', $values)) {
                    $record('lmp', $this->parser->date($values['lmp']), $profile->last_menstrual_period?->toDateString());
                }
                if (array_key_exists('edd', $values)) {
                    $record('edd', $this->parser->date($values['edd']), $profile->estimated_due_date?->toDateString());
                }
                if (array_key_exists('gestational_age', $values)) {
                    $parsed = $this->parser->gestationalAge($values['gestational_age']);
                    $fields['gestational_age'] = [
                        'consultation_value' => $parsed['value'],
                        'profile_value' => $profile->gestational_age_weeks,
                        'confidence' => $parsed['confidence'],
                    ];
                    $warnings = array_merge($warnings, $parsed['warnings']);

                    if ($parsed['confidence'] === ObgynEntryValueParser::CONFIDENCE_UNPARSEABLE) {
                        $unparseable = true;
                    } else {
                        $ga = $this->parser->compareGestationalAge(
                            $parsed['value'],
                            $profile->gestational_age_weeks,
                            $profile->gestational_age_days,
                            $profile->dating_method instanceof \BackedEnum
                                ? $profile->dating_method->value
                                : $profile->dating_method,
                        );
                        $warnings = array_merge($warnings, $ga['warnings']);

                        if (! $ga['matches'] && $ga['difference_days'] !== null) {
                            $conflicts[] = 'gestational_age_mismatch';
                        }
                    }
                }
                $hasTarget = true;
                break;

            case 'antenatal_vitals':
            case 'fetal_assessment':
                if (array_key_exists('blood_pressure', $values)) {
                    $record('blood_pressure', $this->parser->bloodPressure($values['blood_pressure']));
                }
                if (array_key_exists('fundal_height', $values)) {
                    $record('fundal_height', $this->parser->fundalHeight($values['fundal_height']));
                }
                // The ANC visit is the target; a link is only safe if one exists.
                $hasTarget = AntenatalVisit::query()
                    ->where('pregnancy_profile_id', $profile->id)
                    ->exists();
                break;

            default:
                // In scope for the audit, but with no field-level comparison
                // defined yet — reported without a migration recommendation.
                $hasTarget = true;
                break;
        }

        return [
            'fields' => $fields,
            'warnings' => array_values(array_unique($warnings)),
            'conflicts' => array_values(array_unique($conflicts)),
            'unparseable' => $unparseable,
            'has_target' => $hasTarget,
        ];
    }

    private function linkedPregnancyProfileId(?int $consultationId): ?int
    {
        if (! $consultationId) {
            return null;
        }

        $id = ConsultationMaternityLink::query()
            ->forConsultation($consultationId)
            ->forContextType(ConsultationMaternityContextType::PREGNANCY_PROFILE)
            ->active()
            ->value('pregnancy_profile_id');

        return $id ? (int) $id : null;
    }

    /**
     * The patient's single pregnancy profile, or null when there are none or
     * several. Several is ambiguous and must never be resolved automatically.
     */
    private function soleProfileForPatient(int $patientId): ?PregnancyProfile
    {
        $profiles = PregnancyProfile::query()
            ->where('patient_id', $patientId)
            ->orderBy('id')
            ->limit(2)
            ->get();

        return $profiles->count() === 1 ? $profiles->first() : null;
    }
}

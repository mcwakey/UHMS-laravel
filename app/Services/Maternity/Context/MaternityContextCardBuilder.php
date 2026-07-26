<?php

namespace App\Services\Maternity\Context;

use App\Data\Maternity\OperationalMaternityContext;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use Illuminate\Support\Collection;

/**
 * Phase 14R.5 — shared READ-ONLY maternity summary cards.
 *
 * Prepares plain presentation arrays from records the resolver already loaded.
 * The Blade partials that render these perform **zero** queries: this builder
 * touches only in-memory attributes and eager-loaded relations, and never
 * issues a query of its own.
 *
 * It also never recreates maternity business logic — risk levels, readiness and
 * status all come straight from the maternity records that own them.
 *
 * Every card carries:
 *   owner  → the module that owns the record (always Maternity here)
 *   record → the human-readable source record identity
 *   url    → where to open it (null when the route is unavailable)
 */
class MaternityContextCardBuilder
{
    public const OWNER_MATERNITY = 'maternity';

    /**
     * @return list<array<string, mixed>>
     */
    public function forContext(OperationalMaternityContext $context): array
    {
        if (! $context->isResolved() && ! $context->isSuggested()) {
            return [];
        }

        return array_values(array_filter([
            $this->pregnancyCard($context->pregnancyProfile),
            $this->ancCard($context->antenatalVisit),
            $this->laborCard($context->laborEpisode),
            $this->deliveryCard($context->deliveryRecord),
            $this->newbornCard($context->newbornRecords ?? collect()),
            $this->postnatalCard($context->postnatalCase),
        ]));
    }

    /* ── Individual cards ──────────────────────────────────────────────── */

    public function pregnancyCard(?PregnancyProfile $profile): ?array
    {
        if (! $profile) {
            return null;
        }

        return $this->card(
            key: 'pregnancy',
            title: __('maternity_handoffs.cards.pregnancy'),
            record: __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
            ]),
            url: $this->route('admin.maternity.pregnancies.show', $profile),
            rows: [
                [__('maternity_handoffs.fields.status'), $this->enumLabel($profile->profile_status)],
                [__('maternity_handoffs.fields.gestational_age'), $this->gestationalAge($profile)],
                [__('maternity_handoffs.fields.edd'), $profile->estimated_due_date?->format('d M Y')],
                [__('maternity_handoffs.fields.dating_method'), $this->enumLabel($profile->dating_method)],
                [__('maternity_handoffs.fields.risk'), $this->riskSummary($profile)],
            ],
        );
    }

    public function ancCard(?AntenatalVisit $visit): ?array
    {
        if (! $visit) {
            return null;
        }

        return $this->card(
            key: 'anc',
            title: __('maternity_handoffs.cards.anc'),
            record: __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.anc'), 'id' => $visit->id,
            ]),
            url: $this->route('admin.maternity.antenatal.show', $visit),
            rows: [
                [__('maternity_handoffs.fields.latest_visit'), $visit->visit_date?->format('d M Y')],
                [__('maternity_handoffs.fields.visit_number'), $visit->visit_number],
                [__('maternity_handoffs.fields.next_visit'), $visit->next_visit_date?->format('d M Y')],
                [__('maternity_handoffs.fields.status'), $this->enumLabel($visit->status)],
            ],
        );
    }

    public function laborCard(?LaborEpisode $episode): ?array
    {
        if (! $episode) {
            return null;
        }

        return $this->card(
            key: 'labor',
            title: __('maternity_handoffs.cards.labor'),
            record: __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.labor'), 'id' => $episode->id,
            ]),
            url: $this->route('admin.maternity.labor.show', $episode),
            rows: [
                [__('maternity_handoffs.fields.status'), $this->enumLabel($episode->status)],
                [__('maternity_handoffs.fields.stage'), $this->enumLabel($episode->labor_stage)],
                [__('maternity_handoffs.fields.started_at'), $episode->started_at?->format('d M Y H:i')],
                [__('maternity_handoffs.fields.risk'), $this->enumLabel($episode->risk_level)],
                [
                    __('maternity_handoffs.fields.escalation'),
                    $episode->emergency_escalation_required
                        ? __('maternity_handoffs.fields.emergency_escalation_flagged')
                        : null,
                ],
            ],
        );
    }

    public function deliveryCard(?DeliveryRecord $delivery): ?array
    {
        if (! $delivery) {
            return null;
        }

        return $this->card(
            key: 'delivery',
            title: __('maternity_handoffs.cards.delivery'),
            record: __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.delivery'), 'id' => $delivery->id,
            ]),
            url: $this->route('admin.maternity.deliveries.show', $delivery),
            rows: [
                [__('maternity_handoffs.fields.delivered_at'), $delivery->delivery_at?->format('d M Y H:i')],
                [__('maternity_handoffs.fields.mode'), $this->enumLabel($delivery->delivery_mode)],
                [__('maternity_handoffs.fields.outcome'), $this->enumLabel($delivery->delivery_outcome)],
                [__('maternity_handoffs.fields.status'), $this->enumLabel($delivery->status)],
            ],
        );
    }

    /**
     * A newborn SUMMARY, built from the already-loaded collection. Never lazily
     * iterates relations — there is no N+1 here even for multiples.
     *
     * @param  Collection<int, NewbornRecord>  $newborns
     */
    public function newbornCard(Collection $newborns): ?array
    {
        if ($newborns->isEmpty()) {
            return null;
        }

        $first = $newborns->first();

        return $this->card(
            key: 'newborn',
            title: __('maternity_handoffs.cards.newborn'),
            record: __('maternity_handoffs.cards.record_count', ['count' => $newborns->count()]),
            url: $this->route('admin.maternity.newborns.show', $first),
            rows: [
                [__('maternity_handoffs.fields.newborn_count'), (string) $newborns->count()],
                [
                    __('maternity_handoffs.fields.birth_weights'),
                    $newborns->pluck('birth_weight_kg')->filter()->map(fn ($kg) => $kg.' kg')->implode(', ') ?: null,
                ],
                [
                    __('maternity_handoffs.fields.apgar_5'),
                    $newborns->pluck('apgar_5_min')->filter()->implode(', ') ?: null,
                ],
            ],
        );
    }

    public function postnatalCard(?PostnatalCase $case): ?array
    {
        if (! $case) {
            return null;
        }

        return $this->card(
            key: 'postnatal',
            title: __('maternity_handoffs.cards.postnatal'),
            record: __('maternity_handoffs.cards.record_ref', [
                'type' => __('maternity_handoffs.cards.postnatal'), 'id' => $case->id,
            ]),
            url: $this->route('admin.maternity.postnatal.show', $case),
            rows: [
                [__('maternity_handoffs.fields.status'), $this->enumLabel($case->status)],
                [
                    __('maternity_handoffs.fields.mother_ready'),
                    $case->mother_ready_at ? $case->mother_ready_at->format('d M Y H:i') : __('maternity_handoffs.fields.not_ready'),
                ],
                [
                    __('maternity_handoffs.fields.newborn_ready'),
                    $case->newborn_ready_at ? $case->newborn_ready_at->format('d M Y H:i') : __('maternity_handoffs.fields.not_ready'),
                ],
                [
                    __('maternity_handoffs.fields.referral'),
                    $case->referral_required ? __('maternity_handoffs.fields.referral_required') : null,
                ],
            ],
            note: __('maternity_handoffs.postnatal.readiness_advisory'),
        );
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    /**
     * @param  list<array{0: string, 1: string|null}>  $rows
     */
    private function card(
        string $key,
        string $title,
        string $record,
        ?string $url,
        array $rows,
        ?string $note = null,
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'owner' => self::OWNER_MATERNITY,
            'owner_label' => __('maternity_handoffs.ownership.maternity_longitudinal_record'),
            'record' => $record,
            'url' => $url,
            'note' => $note,
            'rows' => array_values(array_map(
                fn ($row) => ['label' => $row[0], 'value' => $row[1]],
                array_filter($rows, fn ($row) => filled($row[1]))
            )),
        ];
    }

    private function gestationalAge(PregnancyProfile $profile): ?string
    {
        if ($profile->gestational_age_weeks === null) {
            return null;
        }

        return trim(sprintf(
            '%dw %dd',
            (int) $profile->gestational_age_weeks,
            (int) ($profile->gestational_age_days ?? 0)
        ));
    }

    /**
     * Risk shown as the maternity record states it — this builder does not
     * compute or re-score risk.
     */
    private function riskSummary(PregnancyProfile $profile): ?string
    {
        $flags = collect([
            $profile->previous_caesarean ? __('maternity_handoffs.risks.previous_caesarean') : null,
            $profile->previous_postpartum_haemorrhage ? __('maternity_handoffs.risks.previous_pph') : null,
            $profile->hypertensive_disorder_risk ? __('maternity_handoffs.risks.hypertensive') : null,
            $profile->diabetes_risk ? __('maternity_handoffs.risks.diabetes') : null,
            $profile->multiple_pregnancy ? __('maternity_handoffs.risks.multiple') : null,
        ])->filter();

        $known = collect($profile->known_risks ?? [])->filter()->values();

        $all = $flags->merge($known);

        return $all->isEmpty() ? null : $all->implode(', ');
    }

    private function enumLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return method_exists($value, 'label') ? $value->label() : (string) $value->value;
        }

        return (string) $value;
    }

    /** Route helper that degrades to null rather than throwing in any context. */
    private function route(string $name, mixed $parameter): ?string
    {
        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}

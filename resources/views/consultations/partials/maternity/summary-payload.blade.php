{{--
    Phase 14R.6.1 — the ONE renderer for a curated maternity payload.

    Used by BOTH the live projection and the completion snapshot, so a
    historical view and a current view can never present the same data
    differently. It reads only the canonical payload array — no models, no
    services, ZERO queries.

    Only the curated Phase 14R.6 fields exist in that payload, so excluded
    narrative (ANC assessment/plan, labor notes, delivery notes, postnatal
    observation values, STI/sexual history, protected fields, billing, pharmacy,
    stock) is structurally absent rather than filtered here.

    Expects: $payload (array), $compact (bool, print mode)
--}}
@php
    $payload = $payload ?? [];
    $compact = $compact ?? false;
    $t = fn (string $key) => __('consultation_maternity_summary.summary.sections.'.$key);
    $f = fn (string $key) => __('maternity_handoffs.fields.'.$key);

    // Enum/status codes are localised at RENDER time; the payload stores codes.
    $enum = function (?string $group, mixed $code) {
        if ($code === null || $code === '') {
            return null;
        }
        $key = 'consultation_maternity.'.$group.'.'.$code;
        $label = __($key);
        return $label === $key ? (string) $code : $label;
    };
    $yesNo = fn (?bool $v) => $v === null ? null : ($v ? __('common.yes') : __('common.no'));
    $codes = fn (?array $list) => empty($list) ? null : implode(', ', $list);
@endphp

@if (empty($payload['pregnancy_profile_id']))
    <p class="text-muted small mb-0">{{ __('maternity_handoffs.cards.no_context') }}</p>
@else
    @php
        $groups = [
            'pregnancy' => array_filter([
                $f('status') => $enum('profile_statuses', $payload['pregnancy']['status'] ?? null),
                __('maternity_handoffs.modal.gravida') => $payload['pregnancy']['gravida'] ?? null,
                __('maternity_handoffs.modal.para') => $payload['pregnancy']['para'] ?? null,
                __('consultation_maternity_summary.summary.abortions') => $payload['pregnancy']['abortions'] ?? null,
                __('consultation_maternity_summary.summary.living_children') => $payload['pregnancy']['living_children'] ?? null,
                __('maternity_handoffs.risks.previous_caesarean') => $yesNo($payload['pregnancy']['previous_caesarean'] ?? null),
                __('maternity_handoffs.modal.last_menstrual_period') => $payload['pregnancy']['lmp'] ?? null,
                $f('edd') => $payload['pregnancy']['edd'] ?? null,
                $f('gestational_age') => isset($payload['pregnancy']['gestational_age_weeks'])
                    ? sprintf('%dw %dd', $payload['pregnancy']['gestational_age_weeks'], $payload['pregnancy']['gestational_age_days'] ?? 0)
                    : null,
                $f('dating_method') => $enum('dating_methods', $payload['pregnancy']['dating_method'] ?? null),
                $f('risk') => $codes($payload['pregnancy']['risk_codes'] ?? null),
            ], fn ($v) => $v !== null && $v !== ''),

            'anc' => array_filter([
                $f('latest_visit') => $payload['anc']['visit_date'] ?? null,
                $f('visit_number') => $payload['anc']['visit_number'] ?? null,
                $f('gestational_age') => isset($payload['anc']['gestational_age_weeks'])
                    ? sprintf('%dw %dd', $payload['anc']['gestational_age_weeks'], $payload['anc']['gestational_age_days'] ?? 0)
                    : null,
                __('consultation_maternity_summary.summary.blood_pressure') => isset($payload['anc']['blood_pressure_systolic'])
                    ? $payload['anc']['blood_pressure_systolic'].'/'.($payload['anc']['blood_pressure_diastolic'] ?? '—')
                    : null,
                __('consultation_maternity_summary.summary.weight') => isset($payload['anc']['weight_kg']) ? $payload['anc']['weight_kg'].' kg' : null,
                __('consultation_maternity_summary.summary.fundal_height') => isset($payload['anc']['fundal_height_cm']) ? $payload['anc']['fundal_height_cm'].' cm' : null,
                __('consultation_maternity_summary.summary.fetal_heart_rate') => $payload['anc']['fetal_heart_rate'] ?? null,
                __('consultation_maternity_summary.summary.presentation') => $payload['anc']['presentation'] ?? null,
                __('consultation_maternity_summary.summary.danger_signs') => $codes($payload['anc']['danger_sign_codes'] ?? null),
                __('consultation_maternity_summary.summary.risk_flags') => $codes($payload['anc']['risk_flag_codes'] ?? null),
                $f('next_visit') => $payload['anc']['next_visit_date'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),

            'labor' => array_filter([
                $f('stage') => $enum('labor_stages', $payload['labor']['stage'] ?? null),
                $f('status') => $enum('labor_statuses', $payload['labor']['status'] ?? null),
                __('consultation_maternity_summary.summary.latest_observation') => $payload['labor']['latest_observation_at'] ?? null,
                __('consultation_maternity_summary.summary.cervical_dilation') => isset($payload['labor']['cervical_dilation_cm']) ? $payload['labor']['cervical_dilation_cm'].' cm' : null,
                __('consultation_maternity_summary.summary.fetal_heart_rate') => $payload['labor']['fetal_heart_rate'] ?? null,
                __('consultation_maternity_summary.summary.blood_pressure') => isset($payload['labor']['blood_pressure_systolic'])
                    ? $payload['labor']['blood_pressure_systolic'].'/'.($payload['labor']['blood_pressure_diastolic'] ?? '—')
                    : null,
                $f('escalation') => collect([
                    ($payload['labor']['emergency_escalation_required'] ?? false) ? __('maternity_handoffs.fields.emergency_escalation_flagged') : null,
                    ($payload['labor']['theatre_escalation_required'] ?? false) ? __('consultation_maternity_summary.summary.theatre_escalation') : null,
                ])->filter()->implode(', ') ?: null,
            ], fn ($v) => $v !== null && $v !== ''),

            'delivery' => array_filter([
                $f('delivered_at') => $payload['delivery']['delivery_at'] ?? null,
                $f('mode') => $payload['delivery']['delivery_mode'] ?? null,
                $f('outcome') => $payload['delivery']['outcome'] ?? null,
                __('consultation_maternity_summary.summary.maternal_condition') => $payload['delivery']['maternal_condition'] ?? null,
                __('consultation_maternity_summary.summary.estimated_blood_loss') => isset($payload['delivery']['estimated_blood_loss_ml']) ? $payload['delivery']['estimated_blood_loss_ml'].' ml' : null,
                $f('newborn_count') => $payload['delivery']['newborn_count'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),

            'postnatal' => array_filter([
                $f('status') => $payload['postnatal']['status'] ?? null,
                $f('mother_ready') => $payload['postnatal']['mother_ready_at'] ?? __('maternity_handoffs.fields.not_ready'),
                $f('newborn_ready') => $payload['postnatal']['newborn_ready_at'] ?? __('maternity_handoffs.fields.not_ready'),
                __('consultation_maternity_summary.summary.ready_for_discharge') => $payload['postnatal']['ready_for_discharge_at'] ?? null,
                $f('referral') => ($payload['postnatal']['referral_required'] ?? false) ? __('maternity_handoffs.fields.referral_required') : null,
                __('consultation_maternity_summary.summary.follow_up_date') => $payload['postnatal']['follow_up_date'] ?? null,
                __('consultation_maternity_summary.summary.latest_mother_observation') => $payload['postnatal']['latest_mother_observation_at'] ?? null,
                __('consultation_maternity_summary.summary.latest_newborn_observation') => $payload['postnatal']['latest_newborn_observation_at'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        ];
        $groups = array_filter($groups, fn ($rows, $key) => $rows !== [] && ! empty($payload[$key]), ARRAY_FILTER_USE_BOTH);
    @endphp

    <div class="row g-2 maternity-summary-payload">
        @foreach ($groups as $key => $rows)
            <div class="{{ $compact ? 'col-12' : 'col-12 col-lg-6' }}">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-1">{{ $t($key) }}</div>
                    <dl class="row mb-0 g-0" style="font-size: .8rem;">
                        @foreach ($rows as $label => $value)
                            <dt class="col-6 fw-normal text-muted">{{ $label }}</dt>
                            <dd class="col-6 mb-1 text-end">{{ $value }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
        @endforeach

        @if (! empty($payload['newborns']))
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="fw-semibold small mb-1">{{ $t('newborn') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: .78rem;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('consultation_maternity_summary.summary.sex') }}</th>
                                    <th>{{ $f('birth_weights') }}</th>
                                    <th>{{ __('consultation_maternity_summary.summary.apgar') }}</th>
                                    <th>{{ __('consultation_maternity_summary.summary.resuscitation') }}</th>
                                    <th>{{ $f('outcome') }}</th>
                                    <th>{{ $f('status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Order is fixed in the payload (birth_order, id) — never re-sorted here. --}}
                                @foreach ($payload['newborns'] as $newborn)
                                    <tr>
                                        <td>{{ $newborn['birth_order'] ?? '—' }}</td>
                                        <td>{{ $newborn['sex'] ?? '—' }}</td>
                                        <td>{{ isset($newborn['birth_weight_kg']) ? $newborn['birth_weight_kg'].' kg' : '—' }}</td>
                                        <td>{{ collect([$newborn['apgar_1_min'] ?? null, $newborn['apgar_5_min'] ?? null, $newborn['apgar_10_min'] ?? null])->filter(fn ($v) => $v !== null)->implode(' / ') ?: '—' }}</td>
                                        <td>{{ $yesNo($newborn['resuscitation_required'] ?? null) ?? '—' }}</td>
                                        <td>{{ $newborn['outcome'] ?? '—' }}</td>
                                        <td>{{ $newborn['status'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if (! empty($payload['admission']))
            <div class="col-12">
                <div class="border rounded p-2">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="fw-semibold small">{{ $t('admission') }}</div>
                        {{-- Ward and bed belong to Admission, not Maternity. --}}
                        <span class="badge bg-light text-secondary border fw-normal">
                            {{ __('consultation_maternity_summary.summary.operational_owner_admission') }}
                        </span>
                    </div>
                    <dl class="row mb-0 mt-1 g-0" style="font-size: .8rem;">
                        <dt class="col-6 fw-normal text-muted">{{ $t('admission') }}</dt>
                        <dd class="col-6 mb-1 text-end">#{{ $payload['admission']['id'] ?? '—' }}</dd>
                        <dt class="col-6 fw-normal text-muted">{{ $f('status') }}</dt>
                        <dd class="col-6 mb-1 text-end">{{ $payload['admission']['status'] ?? '—' }}</dd>
                        <dt class="col-6 fw-normal text-muted">{{ __('consultation_maternity_summary.summary.ward') }}</dt>
                        <dd class="col-6 mb-1 text-end">{{ $payload['admission']['ward_id'] ?? '—' }}</dd>
                        <dt class="col-6 fw-normal text-muted">{{ __('consultation_maternity_summary.summary.bed') }}</dt>
                        <dd class="col-6 mb-1 text-end">{{ $payload['admission']['bed_id'] ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
@endif

{{--
    Phase 14R.4.1 — small Gynaecology Pregnancy Context card.

    Deliberately NOT an Obstetrics-style ribbon or stage-aware panel. Consumes
    the prepared GynaecologyWorkspaceViewModel and performs NO queries.

    Renders nothing unless the Gynaecology context flag is on AND the resolved
    specialty profile is Gynaecology. Context is EXPLICIT-ONLY: an inferred
    same-visit / same-admission / single-active profile is never shown.
--}}
@props(['gynaecology'])

@if($gynaecology?->shouldRender())
@once
@push('styles')
<style>
    .gynae-pregnancy-card { border-left: 4px solid #a855f7; background: #faf5ff; }
    .gynae-pregnancy-card .gp-label {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280;
    }
    .gynae-pregnancy-card .gp-value { font-size: .85rem; font-weight: 600; color: #111827; }
</style>
@endpush
@endonce

@php
    $p = $gynaecology->pregnancyProfile;
    $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y') : '—';
@endphp

<div class="card gynae-pregnancy-card mb-3" id="gynaecology-pregnancy-context">
    <div class="card-body py-2">

        {{-- Rollout marker — only for users who may view maternity context. --}}
        @can('consultation.maternity_context.view')
            <div class="small mb-2 {{ $gynaecology->writeGuardEnabled ? 'text-info-emphasis' : 'text-warning-emphasis' }}">
                <i class="ti {{ $gynaecology->writeGuardEnabled ? 'ti-shield-check' : 'ti-flask' }} me-1"></i>
                <strong>
                    {{ $gynaecology->writeGuardEnabled
                        ? __('consultation_maternity.gynaecology.source_of_truth_mode')
                        : __('consultation_maternity.gynaecology.pilot_mode') }}
                </strong>
                <span class="text-muted">
                    {{ $gynaecology->writeGuardEnabled
                        ? __('consultation_maternity.gynaecology.source_of_truth_hint')
                        : __('consultation_maternity.gynaecology.pilot_hint') }}
                </span>
            </div>
        @endcan

        {{-- Positive pregnancy test: non-blocking affordance only. Never
             creates or links anything, never switches specialty. --}}
        @if($gynaecology->positivePregnancyTestRecorded && ! $gynaecology->hasExplicitLink)
            <div class="alert alert-warning py-2 px-2 small mb-2">
                <i class="ti ti-alert-circle me-1"></i>
                <strong>{{ __('consultation_maternity.gynaecology.positive_test_recorded') }}</strong>
                — {{ __('consultation_maternity.gynaecology.no_profile_created_automatically') }}
            </div>
        @endif

        @if($gynaecology->showsLinkAffordance())
            {{-- Unlinked: discreet, permission-gated action only. No GA/EDD/risk
                 is shown, because no profile is explicitly linked. --}}
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="small text-muted">
                    <i class="ti ti-baby-carriage me-1"></i>{{ __('consultation_maternity.gynaecology.no_profile_linked') }}
                </div>
                @if($gynaecology->can('link') || $gynaecology->can('create_profile'))
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#gynaeLinkPregnancyModal">
                        <i class="ti ti-link me-1"></i>{{ __('consultation_maternity.gynaecology.start_or_link') }}
                    </button>
                @endif
            </div>

        @elseif($gynaecology->showsCard())
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="fw-semibold small">
                    <i class="ti ti-baby-carriage me-1"></i>{{ __('consultation_maternity.gynaecology.explicitly_linked_profile') }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border">{{ __('consultation_maternity.gynaecology.source_profile') }}</span>
                    @if($gynaecology->activeStageBadge)
                        <span class="badge bg-purple-subtle text-purple">{{ $gynaecology->activeStageBadge }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3 mb-2">
                @if($p?->profile_status)
                    <div><div class="gp-label">{{ __('common.status') }}</div>
                        <div class="gp-value">{{ $p->profile_status->label() }}</div></div>
                @endif
                <div><div class="gp-label">LMP</div>
                    <div class="gp-value">{{ $fmt($p?->last_menstrual_period) }}</div></div>
                <div><div class="gp-label">{{ __('consultation_maternity.ribbon.edd') }}</div>
                    <div class="gp-value">{{ $fmt($p?->estimated_due_date) }}</div></div>
                <div><div class="gp-label">{{ __('consultation_maternity.ribbon.gestational_age') }}</div>
                    <div class="gp-value">{{ $p?->gestational_age_weeks ?? '—' }}w {{ $p?->gestational_age_days ?? 0 }}d</div></div>
                @if($p?->dating_method)
                    <div><div class="gp-label">{{ __('consultation_maternity.ribbon.dating_method') }}</div>
                        <div class="gp-value">{{ $p->dating_method->label() }}</div></div>
                @endif
                @if($gynaecology->latestAncDate)
                    <div><div class="gp-label">{{ __('consultation_maternity.ribbon.latest_anc') }}</div>
                        <div class="gp-value">{{ $gynaecology->latestAncDate }}</div></div>
                @endif
            </div>

            {{-- The defining statement: linking never converts the encounter. --}}
            <div class="small text-muted fst-italic mb-2">
                <i class="ti ti-info-circle me-1"></i>{{ __('consultation_maternity.gynaecology.remains_gynaecology') }}
            </div>

            <div class="d-flex flex-wrap gap-2">
                @if($gynaecology->maternityProfileUrl)
                    <a href="{{ $gynaecology->maternityProfileUrl }}" data-no-inertia
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-external-link me-1"></i>{{ __('consultation_maternity.gynaecology.open_maternity_profile') }}
                    </a>
                @endif

                {{-- LMP adoption appears ONLY when genuinely eligible. Other
                     states render their reason instead of a dead button. --}}
                @if($gynaecology->can('adopt_lmp'))
                    @if($gynaecology->canAdoptLmp())
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#gynaeAdoptLmpModal">
                            <i class="ti ti-calendar-check me-1"></i>{{ __('consultation_maternity.lmp.adopt') }}
                        </button>
                    @elseif($gynaecology->lmpAdoptionState === \App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel::ADOPT_CONFLICT)
                        <span class="badge bg-warning-subtle text-warning-emphasis align-self-center">
                            {{ __('consultation_maternity.lmp.conflict') }}
                        </span>
                    @elseif($gynaecology->lmpAdoptionState === \App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel::ADOPT_DATING_LOCKED)
                        <span class="badge bg-secondary-subtle text-secondary align-self-center">
                            {{ __('consultation_maternity.lmp.dating_locked') }}
                        </span>
                    @elseif($gynaecology->lmpAdoptionState === \App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel::ADOPT_IDEMPOTENT)
                        <span class="badge bg-success-subtle text-success align-self-center">
                            {{ __('consultation_maternity.lmp.already_matches') }}
                        </span>
                    @endif
                @endif

                @if($gynaecology->can('relink'))
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#gynaeRelinkModal">
                        {{ __('consultation_maternity.actions.relink_profile') }}
                    </button>
                @endif
                @if($gynaecology->can('unlink'))
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#gynaeUnlinkModal">
                        {{ __('consultation_maternity.actions.unlink_profile') }}
                    </button>
                @endif
            </div>

            {{-- Deliberately absent: Record ANC, Start Labor, delivery/newborn/
                 postnatal mutations. Those belong to Obstetrics/Maternity, and
                 holding those permissions must not surface them here. --}}
        @endif
    </div>
</div>
@endif

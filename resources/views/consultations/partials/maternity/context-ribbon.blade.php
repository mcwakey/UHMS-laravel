{{--
    Phase 14R.3 — Maternity context ribbon (read-only projection).

    Consumes the prepared ObstetricWorkspaceViewModel. This component performs
    NO database queries: every value below was preloaded by
    ObstetricConsultationContextService.

    Renders nothing unless the workspace feature flag is on AND the active
    specialty profile is Obstetrics.
--}}
@props(['maternity'])

@if($maternity?->shouldRender())
@once
@push('styles')
<style>
    .maternity-ribbon { border-left: 4px solid #d63384; background: #fff5f9; }
    .maternity-ribbon .mr-item { min-width: 96px; }
    .maternity-ribbon .mr-label {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .05em;
        color: #6b7280; line-height: 1.2;
    }
    .maternity-ribbon .mr-value { font-size: .85rem; font-weight: 600; color: #111827; }
    .maternity-ribbon.is-suggested { border-left-color: #f59e0b; background: #fffbeb; }
    .maternity-ribbon.is-invalid   { border-left-color: #dc2626; background: #fef2f2; }
</style>
@endpush
@endonce

{{-- Phase 14R.3.1 — rollout-mode marker. Only shown to users who may view the
     maternity context, so it never leaks the pilot to unrelated clinicians. --}}
@can('consultation.maternity_context.view')
    <div class="alert {{ $maternity->writeGuardEnabled ? 'alert-info' : 'alert-warning' }} py-1 px-2 mb-2 small d-flex align-items-center gap-2">
        <i class="ti {{ $maternity->writeGuardEnabled ? 'ti-shield-check' : 'ti-flask' }}"></i>
        <div>
            <strong>
                {{ $maternity->writeGuardEnabled
                    ? __('consultation_maternity.rollout.guarded_title')
                    : __('consultation_maternity.rollout.pilot_title') }}
            </strong>
            <span class="text-muted">
                {{ $maternity->writeGuardEnabled
                    ? __('consultation_maternity.rollout.guarded_hint')
                    : __('consultation_maternity.rollout.pilot_hint') }}
            </span>
        </div>
    </div>
@endcan

@php
    $sourceLabel = match($maternity->resolutionSource) {
        'explicit'       => __('consultation_maternity.ribbon.source_maternity'),
        'visit'          => __('consultation_maternity.ribbon.source_same_visit'),
        'admission'      => __('consultation_maternity.ribbon.source_same_admission'),
        'active_profile' => __('consultation_maternity.ribbon.source_active_profile'),
        default          => null,
    };
    $p = $maternity->pregnancy;
    $a = $maternity->anc;
@endphp

{{-- Invalid explicit link: warn, and maternity actions are already disabled. --}}
@if($maternity->isInvalid())
    <div class="card maternity-ribbon is-invalid mb-3">
        <div class="card-body py-2">
            <div class="fw-semibold text-danger mb-1">
                <i class="ti ti-alert-triangle me-1"></i>{{ __('consultation_maternity.statuses.invalid') }}
            </div>
            @foreach($maternity->warnings as $warning)
                <div class="small text-danger-emphasis">{{ $warning }}</div>
            @endforeach
            <div class="small text-muted mt-1">{{ __('consultation_maternity.messages.invalid_context_actions_disabled') }}</div>
        </div>
    </div>

{{-- Ambiguous: never auto-select; the panel renders the candidate selector. --}}
@elseif($maternity->isAmbiguous())
    <div class="card maternity-ribbon is-suggested mb-3">
        <div class="card-body py-2">
            <div class="fw-semibold mb-1">
                <i class="ti ti-alert-circle me-1"></i>{{ __('consultation_maternity.ambiguous_maternity_context') }}
            </div>
            @foreach($maternity->warnings as $warning)
                <div class="small text-warning-emphasis">{{ $warning }}</div>
            @endforeach
        </div>
    </div>

{{-- Resolved: full ribbon when explicit, "suggested" when inferred. --}}
@elseif($maternity->isResolved())
    <div class="card maternity-ribbon {{ $maternity->isExplicit ? '' : 'is-suggested' }} mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="fw-semibold">
                    <i class="ti ti-baby-carriage me-1"></i>
                    {{ $maternity->isExplicit
                        ? __('consultation_maternity.ribbon.title')
                        : __('consultation_maternity.ribbon.suggested') }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($sourceLabel)
                        <span class="badge bg-light text-dark border">{{ $sourceLabel }}</span>
                    @endif
                    @if($maternity->isExplicit)
                        <span class="badge bg-success-subtle text-success">{{ __('consultation_maternity.ribbon.explicitly_linked') }}</span>
                    @endif
                    @if($maternity->mode() === 'pilot')
                        <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('consultation_maternity.ribbon.pilot_mode') }}</span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                @if(!empty($p['gestational_age_weeks']) || !empty($p['gestational_age_days']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.gestational_age') }}</div>
                        <div class="mr-value">{{ $p['gestational_age_weeks'] ?? 0 }}w {{ $p['gestational_age_days'] ?? 0 }}d</div>
                    </div>
                @endif
                @if(!empty($p['edd']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.edd') }}</div>
                        <div class="mr-value">{{ \Illuminate\Support\Carbon::parse($p['edd'])->format('d M Y') }}</div>
                    </div>
                @endif
                @if(!empty($p['dating_method']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.dating_method') }}</div>
                        <div class="mr-value">{{ $p['dating_method']->label() }}</div>
                    </div>
                @endif
                @if(!empty($p['risk_level']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.risk_level') }}</div>
                        <div class="mr-value">{{ is_object($p['risk_level']) ? $p['risk_level']->label() : $p['risk_level'] }}</div>
                    </div>
                @endif
                @if(!empty($a['visit_date']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.latest_anc') }}</div>
                        <div class="mr-value">{{ \Illuminate\Support\Carbon::parse($a['visit_date'])->format('d M Y') }}</div>
                    </div>
                @endif
                @if(!empty($a['next_visit_date']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.next_anc') }}</div>
                        <div class="mr-value">{{ \Illuminate\Support\Carbon::parse($a['next_visit_date'])->format('d M Y') }}</div>
                    </div>
                @endif
                @if(!empty($maternity->admission['ward']) || !empty($maternity->admission['bed']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.ward_bed') }}</div>
                        <div class="mr-value">{{ $maternity->admission['ward'] ?? '—' }} / {{ $maternity->admission['bed'] ?? '—' }}</div>
                    </div>
                @endif
                @if(!empty($maternity->labor['stage']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.labor_stage') }}</div>
                        <div class="mr-value">{{ is_object($maternity->labor['stage']) ? $maternity->labor['stage']->label() : $maternity->labor['stage'] }}</div>
                    </div>
                @endif
                @if(!empty($maternity->delivery['delivery_at']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.delivery_status') }}</div>
                        <div class="mr-value">{{ \Illuminate\Support\Carbon::parse($maternity->delivery['delivery_at'])->format('d M Y H:i') }}</div>
                    </div>
                @endif
                @if(!empty($maternity->newborn['count']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.newborn_records') }}</div>
                        <div class="mr-value">
                            {{ $maternity->newborn['count'] }}{{ !empty($maternity->newborn['pending']) ? ' ⚠' : '' }}
                        </div>
                    </div>
                @endif
                @if(!empty($maternity->postnatal['status']))
                    <div class="mr-item">
                        <div class="mr-label">{{ __('consultation_maternity.ribbon.postnatal_status') }}</div>
                        <div class="mr-value">{{ is_object($maternity->postnatal['status']) ? $maternity->postnatal['status']->label() : $maternity->postnatal['status'] }}</div>
                    </div>
                @endif
            </div>

            {{-- Inferred context must be confirmed before it can be acted on. --}}
            @if($maternity->showsSuggestion() && $maternity->can('link'))
                <form method="POST" action="{{ route('admin.consultations.maternity-context.confirm', $visit) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="ti ti-link me-1"></i>{{ __('consultation_maternity.actions.confirm_and_link') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
@endif

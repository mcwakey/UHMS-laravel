{{--
    Phase 14R.3 — Maternity Context panel (stage-aware, projection + explicit action).

    Consumes the prepared ObstetricWorkspaceViewModel; performs NO queries.
    Only subsections relevant to the resolved context are shown. This is a
    projection surface, not a second set of maternity forms — every mutation is
    an explicit action that delegates to the maternity services.
--}}
@props(['maternity'])

@if($maternity?->shouldRender())
@php
    $p = $maternity->pregnancy;
    $a = $maternity->anc;
    $labor = $maternity->labor;
    $delivery = $maternity->delivery;
    $newborn = $maternity->newborn;
    $postnatal = $maternity->postnatal;
    $fmt = fn ($d, $f = 'd M Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
@endphp

<div class="card mb-3" id="maternity-context-panel">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">
            <i class="ti ti-baby-carriage me-1"></i>{{ __('consultation_maternity.panel.title') }}
        </h6>
        @if($maternity->mode() === 'guarded')
            <span class="badge bg-info-subtle text-info">{{ __('consultation_maternity.panel.read_only_reason') }}</span>
        @endif
    </div>
    <div class="card-body">

        {{-- No context: discreet, permission-gated entry point only. --}}
        @if($maternity->isNone())
            <p class="text-muted small mb-2">{{ __('consultation_maternity.no_maternity_context') }}</p>
            @if($maternity->can('link') || $maternity->can('create_profile'))
                <button type="button" class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#maternityLinkProfileModal">
                    <i class="ti ti-link me-1"></i>{{ __('consultation_maternity.actions.link_or_create_profile') }}
                </button>
            @endif

        {{-- Ambiguous: explicit selector, never an automatic choice. --}}
        @elseif($maternity->isAmbiguous())
            <p class="text-warning-emphasis small">{{ __('consultation_maternity.multiple_active_pregnancy_profiles') }}</p>
            @if($maternity->can('link'))
                <form method="POST" action="{{ route('admin.consultations.maternity-context.link', $visit) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px"></th>
                                    <th>{{ __('common.status') }}</th>
                                    <th>LMP</th>
                                    <th>EDD</th>
                                    <th>{{ __('consultation_maternity.ribbon.gestational_age') }}</th>
                                    <th>{{ __('common.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($maternity->candidateProfiles ?? [] as $candidate)
                                    <tr>
                                        <td><input type="radio" name="pregnancy_profile_id" value="{{ $candidate->id }}" required></td>
                                        <td>{{ $candidate->profile_status?->label() ?? '—' }}</td>
                                        <td>{{ $fmt($candidate->last_menstrual_period) }}</td>
                                        <td>{{ $fmt($candidate->estimated_due_date) }}</td>
                                        <td>{{ $candidate->gestational_age_weeks ?? '—' }}w {{ $candidate->gestational_age_days ?? 0 }}d</td>
                                        <td>{{ $fmt($candidate->created_at) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-check me-1"></i>{{ __('consultation_maternity.actions.select_profile') }}
                    </button>
                </form>
            @endif

        {{-- Invalid: actions disabled until corrected. --}}
        @elseif($maternity->isInvalid())
            <div class="alert alert-danger py-2 mb-0">
                <div class="fw-semibold">{{ __('consultation_maternity.statuses.invalid') }}</div>
                @foreach($maternity->warnings as $warning)<div class="small">{{ $warning }}</div>@endforeach
            </div>

        {{-- Resolved: stage-aware projections. --}}
        @else
            <ul class="nav nav-tabs nav-tabs-sm mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mc-pregnancy" type="button">{{ __('consultation_maternity.panel.pregnancy') }}</button></li>
                @if($a)<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mc-anc" type="button">{{ __('consultation_maternity.panel.anc') }}</button></li>@endif
                @if($labor || $delivery)<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mc-labor" type="button">{{ __('consultation_maternity.panel.labor_delivery') }}</button></li>@endif
                @if($newborn)<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mc-newborn" type="button">{{ __('consultation_maternity.panel.newborn') }}</button></li>@endif
                @if($postnatal)<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mc-postnatal" type="button">{{ __('consultation_maternity.panel.postnatal') }}</button></li>@endif
            </ul>

            <div class="tab-content">
                {{-- Pregnancy --}}
                <div class="tab-pane fade show active" id="mc-pregnancy">
                    <div class="row g-2 small">
                        <div class="col-md-3"><span class="text-muted">Gravida:</span> <strong>{{ $p['gravida'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">Para:</span> <strong>{{ $p['para'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">LMP:</span> <strong>{{ $fmt($p['lmp'] ?? null) }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">EDD:</span> <strong>{{ $fmt($p['edd'] ?? null) }}</strong></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        @if($maternity->can('relink'))
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#maternityRelinkModal">
                                {{ __('consultation_maternity.actions.relink_profile') }}
                            </button>
                        @endif
                        @if($maternity->can('unlink'))
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#maternityUnlinkModal">
                                {{ __('consultation_maternity.actions.unlink_profile') }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ANC --}}
                @if($a)
                <div class="tab-pane fade" id="mc-anc">
                    <div class="row g-2 small">
                        <div class="col-md-3"><span class="text-muted">{{ __('consultation_maternity.ribbon.latest_anc') }}:</span> <strong>{{ $fmt($a['visit_date'] ?? null) }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">BP:</span> <strong>{{ $a['blood_pressure'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">Weight:</span> <strong>{{ $a['weight_kg'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">FHR:</span> <strong>{{ $a['fetal_heart_rate'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">Fundal height:</span> <strong>{{ $a['fundal_height_cm'] ?? '—' }}</strong></div>
                        <div class="col-md-3"><span class="text-muted">{{ __('consultation_maternity.ribbon.next_anc') }}:</span> <strong>{{ $fmt($a['next_visit_date'] ?? null) }}</strong></div>
                    </div>
                    @if($maternity->can('record_anc'))
                        <button type="button" class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#maternityRecordAncModal">
                            <i class="ti ti-plus me-1"></i>{{ __('consultation_maternity.actions.record_anc') }}
                        </button>
                    @endif
                </div>
                @endif

                {{-- Labor & delivery --}}
                @if($labor || $delivery)
                <div class="tab-pane fade" id="mc-labor">
                    @if($labor)
                        <div class="row g-2 small mb-2">
                            <div class="col-md-3"><span class="text-muted">{{ __('consultation_maternity.ribbon.labor_stage') }}:</span> <strong>{{ is_object($labor['stage'] ?? null) ? $labor['stage']->label() : ($labor['stage'] ?? '—') }}</strong></div>
                            <div class="col-md-3"><span class="text-muted">{{ __('common.status') }}:</span> <strong>{{ is_object($labor['status'] ?? null) ? $labor['status']->label() : ($labor['status'] ?? '—') }}</strong></div>
                            @if(!empty($labor['latest_observation']))
                                <div class="col-md-3"><span class="text-muted">Dilation:</span> <strong>{{ $labor['latest_observation']['cervical_dilation_cm'] ?? '—' }} cm</strong></div>
                                <div class="col-md-3"><span class="text-muted">FHR:</span> <strong>{{ $labor['latest_observation']['fetal_heart_rate'] ?? '—' }}</strong></div>
                            @endif
                        </div>
                    @endif
                    @if($delivery)
                        <div class="row g-2 small">
                            <div class="col-md-3"><span class="text-muted">Delivered:</span> <strong>{{ $fmt($delivery['delivery_at'] ?? null, 'd M Y H:i') }}</strong></div>
                            <div class="col-md-3"><span class="text-muted">Mode:</span> <strong>{{ $delivery['delivery_mode'] ?? '—' }}</strong></div>
                            <div class="col-md-3"><span class="text-muted">Outcome:</span> <strong>{{ $delivery['delivery_outcome'] ?? '—' }}</strong></div>
                            <div class="col-md-3"><span class="text-muted">Newborns:</span> <strong>{{ $delivery['newborn_count'] ?? '—' }}</strong></div>
                        </div>
                    @endif
                    @if(!$labor && $maternity->can('start_labor'))
                        <form method="POST" action="{{ route('admin.consultations.maternity-context.start-labor', $visit) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="ti ti-activity me-1"></i>{{ __('consultation_maternity.actions.start_labor') }}
                            </button>
                        </form>
                    @endif
                </div>
                @endif

                {{-- Newborn --}}
                @if($newborn)
                <div class="tab-pane fade" id="mc-newborn">
                    <table class="table table-sm small">
                        <thead class="table-light"><tr><th>#</th><th>Sex</th><th>Weight</th><th>APGAR 1/5</th></tr></thead>
                        <tbody>
                            @foreach($newborn['records'] ?? [] as $n)
                                <tr>
                                    <td>{{ $n['birth_order'] ?? '—' }}</td>
                                    <td>{{ $n['sex'] ?? '—' }}</td>
                                    <td>{{ $n['birth_weight_kg'] ?? '—' }}</td>
                                    <td>{{ $n['apgar_1_min'] ?? '—' }} / {{ $n['apgar_5_min'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Postnatal --}}
                @if($postnatal)
                <div class="tab-pane fade" id="mc-postnatal">
                    <div class="row g-2 small">
                        <div class="col-md-4"><span class="text-muted">{{ __('common.status') }}:</span> <strong>{{ is_object($postnatal['status'] ?? null) ? $postnatal['status']->label() : ($postnatal['status'] ?? '—') }}</strong></div>
                        <div class="col-md-4"><span class="text-muted">Mother ready:</span> <strong>{{ !empty($postnatal['mother_ready']) ? __('common.yes') : __('common.no') }}</strong></div>
                        <div class="col-md-4"><span class="text-muted">Newborn ready:</span> <strong>{{ !empty($postnatal['newborn_ready']) ? __('common.yes') : __('common.no') }}</strong></div>
                    </div>
                </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endif

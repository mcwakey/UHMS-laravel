@php
    use App\Enums\PatientFinancialRiskLevel;
    use App\Enums\PatientFinancialRiskReason;
    use App\Enums\PatientFinancialRiskStatus;
    $fr = $financialRisk ?? null;
    $status = $fr?->status;
    $canManage = auth()->user()?->can('patients.financial_risk.manage');
    $canReview = auth()->user()?->can('patients.financial_risk.review');
    $canClear = auth()->user()?->can('patients.financial_risk.clear');
    $canHistory = auth()->user()?->can('patients.financial_risk.history');
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-alert-triangle me-1"></i>{{ __('patient_financial_risk.section_title') }}</h6>
        @can('patients.financial_risk.view')
            <a href="{{ route('admin.billing.financial-risk.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-list-search me-1"></i>{{ __('patient_financial_risk.actions.open_worklist') }}
            </a>
        @endcan
    </div>
    <div class="card-body">
        <p class="text-muted small">{{ __('patient_financial_risk.subtitle') }}</p>

        @if(! $fr)
            {{-- Neutral empty state for a normal patient with no active profile --}}
            <div class="text-center text-muted py-4">
                <i class="ti ti-shield-check fs-1 d-block mb-2"></i>
                {{ __('patient_financial_risk.empty_state') }}
            </div>
            @if($canManage)
                <div class="text-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#frClassifyModal">
                        <i class="ti ti-plus me-1"></i>{{ __('patient_financial_risk.actions.classify') }}
                    </button>
                </div>
            @endif
        @else
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-{{ $fr->risk_level->color() }} fs-6">{{ $fr->risk_level->label() }}</span>
                <span class="badge bg-{{ $status->color() }} fs-6">{{ $status->label() }}</span>
                @if($fr->isReviewOverdue())<span class="badge bg-warning text-dark">{{ __('patient_financial_risk.badges.review_overdue') }}</span>@endif
                @if($fr->expires_at && $fr->expires_at->isFuture() && $fr->expires_at->lte(now()->addDays(30)))<span class="badge bg-info">{{ __('patient_financial_risk.badges.expires_soon') }}</span>@endif
            </div>

            <dl class="row mb-3">
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.primary_reason') }}</dt>
                <dd class="col-sm-8">{{ $fr->primary_reason?->label() ?? '—' }}</dd>
                @if($fr->reason_details)
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.reason_details') }}</dt>
                <dd class="col-sm-8">{{ $fr->reason_details }}</dd>
                @endif
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.effective_from') }}</dt>
                <dd class="col-sm-8">{{ optional($fr->effective_from)->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.review_due_at') }}</dt>
                <dd class="col-sm-8">{{ optional($fr->review_due_at)->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.expires_at') }}</dt>
                <dd class="col-sm-8">{{ optional($fr->expires_at)->format('d M Y') ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.credit_limit') }}</dt>
                <dd class="col-sm-8">{{ $fr->credit_limit !== null ? number_format((float) $fr->credit_limit, 2) : '—' }}</dd>
                @if($fr->reference)
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.reference') }}</dt>
                <dd class="col-sm-8">{{ $fr->reference }}</dd>
                @endif
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.set_by') }}</dt>
                <dd class="col-sm-8">{{ $fr->setter?->name ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.reviewed_by') }}</dt>
                <dd class="col-sm-8">{{ $fr->reviewer?->name ?? '—' }}</dd>
                <dt class="col-sm-4 text-muted">{{ __('patient_financial_risk.fields.last_updated') }}</dt>
                <dd class="col-sm-8">{{ $fr->updated_at?->format('d M Y H:i') ?? '—' }}</dd>
            </dl>

            <div class="d-flex flex-wrap gap-2">
                @if($canManage && $status->occupiesActiveSlot())
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#frClassifyModal">
                        <i class="ti ti-edit me-1"></i>{{ __('patient_financial_risk.actions.edit') }}
                    </button>
                @endif
                @if($canReview && $status === PatientFinancialRiskStatus::ACTIVE)
                    @include('patients.partials._financial-risk-action', ['action' => 'submit-review', 'label' => __('patient_financial_risk.actions.submit_review'), 'reasonRequired' => false, 'btn' => 'btn-outline-warning'])
                @endif
                @if($canReview && $status === PatientFinancialRiskStatus::UNDER_REVIEW)
                    @include('patients.partials._financial-risk-action', ['action' => 'complete-review', 'label' => __('patient_financial_risk.actions.complete_review'), 'reasonRequired' => false, 'btn' => 'btn-outline-success'])
                @endif
                @if($canReview && in_array($status, [PatientFinancialRiskStatus::ACTIVE, PatientFinancialRiskStatus::UNDER_REVIEW], true))
                    @include('patients.partials._financial-risk-action', ['action' => 'suspend', 'label' => __('patient_financial_risk.actions.suspend'), 'reasonRequired' => true, 'btn' => 'btn-outline-secondary'])
                @endif
                @if($canReview && $status === PatientFinancialRiskStatus::SUSPENDED)
                    @include('patients.partials._financial-risk-action', ['action' => 'reactivate', 'label' => __('patient_financial_risk.actions.reactivate'), 'reasonRequired' => true, 'btn' => 'btn-outline-primary'])
                @endif
                @if($canClear && ! $status->isTerminal())
                    @include('patients.partials._financial-risk-action', ['action' => 'clear', 'label' => __('patient_financial_risk.actions.clear'), 'reasonRequired' => true, 'btn' => 'btn-outline-danger'])
                @endif
            </div>
        @endif

        {{-- History --}}
        @if($canHistory && ($financialRiskHistory ?? collect())->isNotEmpty())
            <hr class="my-4">
            <h6 class="fw-bold mb-3">{{ __('patient_financial_risk.history.title') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('patient_financial_risk.history.datetime') }}</th>
                            <th>{{ __('patient_financial_risk.history.event') }}</th>
                            <th>{{ __('patient_financial_risk.history.change') }}</th>
                            <th>{{ __('patient_financial_risk.history.performed_by') }}</th>
                            <th>{{ __('patient_financial_risk.history.reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($financialRiskHistory as $entry)
                            @php
                                $old = $entry->old_values ?? [];
                                $new = $entry->new_values ?? [];
                                $oldStatus = isset($old['status']) ? PatientFinancialRiskStatus::tryFrom($old['status'])?->label() : null;
                                $newStatus = isset($new['status']) ? PatientFinancialRiskStatus::tryFrom($new['status'])?->label() : null;
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $entry->performed_at?->format('d M Y H:i') }}</td>
                                <td>{{ $entry->event_type?->label() }}</td>
                                <td class="small text-muted">
                                    @if($oldStatus || $newStatus)
                                        {{ __('patient_financial_risk.history.from_to', ['from' => $oldStatus ?? '—', 'to' => $newStatus ?? '—']) }}
                                    @else — @endif
                                </td>
                                <td>{{ $entry->performer?->name ?? __('patient_financial_risk.history.system') }}</td>
                                <td class="small">{{ $entry->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Classify / edit modal --}}
@if($canManage && (! $fr || $status?->occupiesActiveSlot()))
    @include('patients.partials._financial-risk-form')
@endif

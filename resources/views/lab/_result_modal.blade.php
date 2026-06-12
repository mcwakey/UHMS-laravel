@php
    $result = $item->result;
    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
    $serviceHeaders  = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
@endphp

@if(($noResult ?? false) || !$result)
    <div class="alert alert-warning mb-0"><i class="ti ti-alert-circle me-1"></i>{{ __('lab.no_result_entered') }}</div>
@else
<div class="row g-2 mb-3">
    <div class="col-md-6">
        <small class="text-muted d-block">{{ __('lab.patient_label') }}</small>
        <strong>{{ $item->labRequest->patient->full_name ?? '—' }}</strong>
    </div>
    <div class="col-md-3">
        <small class="text-muted d-block">{{ __('lab.investigation_col') }}</small>
        <strong>{{ $item->display_name }}</strong>
    </div>
    <div class="col-md-3">
        <small class="text-muted d-block">{{ __('lab.status_col') }}</small>
        @if($result->is_verified)
            <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('lab.verified_badge') }}</span>
        @else
            <span class="badge bg-warning">{{ __('lab.pending_verification') }}</span>
        @endif
    </div>
</div>

@if($result->values && $result->values->count())
    @php
        $valuesByCriteria = $result->values->keyBy('criteria_id');
    @endphp
    @foreach($serviceHeaders as $h)
        @php
            $hCriteria = $serviceCriteria->where('header_id', $h->id);
        @endphp
        @if($hCriteria->isNotEmpty())
        <div class="mb-2">
            <h6 class="small fw-bold border-bottom pb-1 mb-2">{{ $h->name }}</h6>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>{{ __('lab.parameter_col') }}</th><th>{{ __('lab.value_col') }}</th><th>{{ __('lab.unit_col') }}</th><th>{{ __('lab.reference_col') }}</th><th>{{ __('lab.flag_col') }}</th></tr></thead>
                <tbody>
                @foreach($hCriteria as $c)
                    @php $v = $valuesByCriteria->get($c->id); @endphp
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td class="fw-medium">{{ $v?->value ?? '—' }}</td>
                        <td>{{ $v?->unit ?? $c->unit }}</td>
                        <td>{{ $v?->reference_range ?? $c->reference_range }}</td>
                        <td>@if($v?->flag)<span class="badge bg-{{ $v->flag === 'normal' ? 'success' : 'warning' }}">{{ ucfirst($v->flag) }}</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        @endif
    @endforeach
    @php $unsorted = $serviceCriteria->whereNull('header_id'); @endphp
    @if($unsorted->isNotEmpty() || ($serviceHeaders->isEmpty() && $serviceCriteria->isEmpty()))
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>{{ __('lab.parameter_col') }}</th><th>{{ __('lab.value_col') }}</th><th>{{ __('lab.unit_col') }}</th><th>{{ __('lab.reference_col') }}</th><th>{{ __('lab.flag_col') }}</th></tr></thead>
        <tbody>
        @foreach($result->values as $v)
            @php $c = $serviceCriteria->firstWhere('id', $v->criteria_id); @endphp
            @if($c === null || $c->header_id === null)
            <tr>
                <td>{{ $v->name }}</td>
                <td class="fw-medium">{{ $v->value }}</td>
                <td>{{ $v->unit }}</td>
                <td>{{ $v->reference_range }}</td>
                <td>@if($v->flag)<span class="badge bg-{{ $v->flag === 'normal' ? 'success' : 'warning' }}">{{ ucfirst($v->flag) }}</span>@endif</td>
            </tr>
            @endif
        @endforeach
        </tbody>
    </table></div>
    @endif
@else
    <div class="card mb-2">
        <div class="card-body py-2">
            <small class="text-muted d-block">{{ __('lab.result_label') }}</small>
            @if($result->result_text)
                <div>{!! nl2br(e($result->result_text)) !!}</div>
            @elseif($result->result_value)
                <strong class="{{ $result->is_abnormal ? 'text-danger' : '' }}">{{ $result->result_value }}</strong>
            @endif
            @if($result->result_file)
                <a href="{{ asset('storage/' . $result->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="ti ti-paperclip me-1"></i>{{ $result->result_file_name ?? __('lab.attachment_label') }}
                </a>
            @endif
        </div>
    </div>
@endif

@if($result->remarks)
<div class="alert alert-light border small mb-2"><strong>{{ __('lab.remarks_label') }}:</strong> {{ $result->remarks }}</div>
@endif

<div class="row g-2 small text-muted">
    <div class="col-md-6">
        <i class="ti ti-user me-1"></i>{{ __('lab.performed_by_label') }} <strong>{{ $result->performedBy?->name ?? '—' }}</strong>
        {{ __('lab.on_label') }} {{ $result->performed_at?->format('d M Y H:i') ?? '—' }}
    </div>
    <div class="col-md-6">
        @if($result->is_verified)
        <i class="ti ti-shield-check text-success me-1"></i>{{ __('lab.verified_by_label') }} <strong>{{ $result->verifiedBy?->name ?? '—' }}</strong>
        {{ __('lab.on_label') }} {{ $result->verified_at?->format('d M Y H:i') ?? '—' }}
        @else
        <i class="ti ti-alert-circle text-warning me-1"></i>{{ __('lab.awaiting_verification') }}
        @endif
    </div>
</div>

@if($result->is_verified)
<div class="mt-3 text-end">
    <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-sm btn-primary">
        <i class="ti ti-printer me-1"></i>{{ __('lab.print_report_btn') }}
    </a>
</div>
@endif
@endif

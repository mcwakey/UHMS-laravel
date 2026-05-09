@php
    $result = $item->result;
    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
    $serviceHeaders  = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
@endphp

@if(($noResult ?? false) || !$result)
    <div class="alert alert-warning mb-0"><i class="ti ti-alert-circle me-1"></i>No result has been entered yet.</div>
@else
<div class="row g-2 mb-3">
    <div class="col-md-6">
        <small class="text-muted d-block">Patient</small>
        <strong>{{ $item->labRequest->patient->full_name ?? '—' }}</strong>
    </div>
    <div class="col-md-3">
        <small class="text-muted d-block">Investigation</small>
        <strong>{{ $item->display_name }}</strong>
    </div>
    <div class="col-md-3">
        <small class="text-muted d-block">Status</small>
        @if($result->is_verified)
            <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
        @else
            <span class="badge bg-warning">Pending Verification</span>
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
            <table class="table table-sm mb-0">
                <thead><tr><th>Parameter</th><th>Value</th><th>Unit</th><th>Reference</th><th>Flag</th></tr></thead>
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
            </table>
        </div>
        @endif
    @endforeach
    @php $unsorted = $serviceCriteria->whereNull('header_id'); @endphp
    @if($unsorted->isNotEmpty() || ($serviceHeaders->isEmpty() && $serviceCriteria->isEmpty()))
    <table class="table table-sm">
        <thead><tr><th>Parameter</th><th>Value</th><th>Unit</th><th>Reference</th><th>Flag</th></tr></thead>
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
    </table>
    @endif
@else
    <div class="card mb-2">
        <div class="card-body py-2">
            <small class="text-muted d-block">Result</small>
            @if($result->result_text)
                <div>{!! nl2br(e($result->result_text)) !!}</div>
            @elseif($result->result_value)
                <strong class="{{ $result->is_abnormal ? 'text-danger' : '' }}">{{ $result->result_value }}</strong>
            @endif
            @if($result->result_file)
                <a href="{{ asset('storage/' . $result->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="ti ti-paperclip me-1"></i>{{ $result->result_file_name ?? 'Attachment' }}
                </a>
            @endif
        </div>
    </div>
@endif

@if($result->remarks)
<div class="alert alert-light border small mb-2"><strong>Remarks:</strong> {{ $result->remarks }}</div>
@endif

<div class="row g-2 small text-muted">
    <div class="col-md-6">
        <i class="ti ti-user me-1"></i>Performed by <strong>{{ $result->performedBy?->name ?? '—' }}</strong>
        on {{ $result->performed_at?->format('d M Y H:i') ?? '—' }}
    </div>
    <div class="col-md-6">
        @if($result->is_verified)
        <i class="ti ti-shield-check text-success me-1"></i>Verified by <strong>{{ $result->verifiedBy?->name ?? '—' }}</strong>
        on {{ $result->verified_at?->format('d M Y H:i') ?? '—' }}
        @else
        <i class="ti ti-alert-circle text-warning me-1"></i>Awaiting verification
        @endif
    </div>
</div>

@if($result->is_verified)
<div class="mt-3 text-end">
    <a href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-sm btn-primary">
        <i class="ti ti-printer me-1"></i>Print Report
    </a>
</div>
@endif
@endif

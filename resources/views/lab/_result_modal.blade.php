@php
    $result = $item->result;
    $labRequest = $item->labRequest;
    $patient = $labRequest->patient;
@endphp

@if(($noResult ?? false) || ! $result)
    <div class="alert alert-warning mb-0">
        <i class="ti ti-alert-circle me-1"></i>{{ __('lab.no_result_entered') }}
    </div>
@else
<style>
    .result-preview-shell { color: #24324a; }
    .result-preview-banner { background: linear-gradient(135deg, #eef5ff, #f8fbff); border: 1px solid #d7e6fa; border-radius: 14px; padding: 16px 18px; }
    .result-preview-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .result-preview-meta small { display: block; color: #748198; text-transform: uppercase; font-size: 10px; letter-spacing: .06em; }
    .result-preview-meta strong { display: block; margin-top: 2px; }
    .result-report-section { margin-top: 18px; border: 1px solid #dfe6ef; border-radius: 14px; overflow: hidden; background: #fff; }
    .result-report-heading { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 16px 18px; border-bottom: 1px solid #e8edf4; }
    .result-report-heading h2 { margin: 0; font-size: 18px; }
    .result-report-kicker { color: #748198; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }
    .result-status { border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .result-status-normal { background: #dcfce7; color: #166534; }
    .result-status-alert { background: #fee2e2; color: #991b1b; }
    .result-group-title { margin: 14px 18px 6px; color: #49617f; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
    .result-table { width: calc(100% - 36px); margin: 0 18px 16px; border-collapse: collapse; }
    .result-table th { color: #667085; background: #f6f8fb; text-transform: uppercase; font-size: 10px; letter-spacing: .05em; }
    .result-table th, .result-table td { border: 1px solid #e4e9f0; padding: 8px 10px; }
    .result-table .result-value { font-weight: 700; }
    .result-flag-normal { background: #f0fdf4; }
    .result-flag-alert { background: #fff7ed; color: #9a3412; }
    .result-narrative, .result-summary-value { margin: 16px 18px; padding: 14px; border-radius: 10px; background: #f8fafc; white-space: pre-wrap; }
    .result-summary-value { font-size: 20px; font-weight: 700; }
    .result-remarks, .result-attachment { margin: 0 18px 14px; padding: 10px 12px; border-left: 3px solid #7c9ac2; background: #f8fafc; }
    .result-preview-signoff { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
    .result-preview-signoff > div { border: 1px solid #e1e7ef; border-radius: 10px; padding: 12px; }
    @media (max-width: 767px) {
        .result-preview-meta, .result-preview-signoff { grid-template-columns: 1fr; }
        .result-report-heading { align-items: flex-start; }
    }
</style>

<div class="result-preview-shell">
    <div class="result-preview-banner">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="text-uppercase small text-muted fw-semibold">{{ __('lab.result_report_title') }}</div>
                <h5 class="mb-0">{{ $labRequest->request_number }}</h5>
            </div>
            <span class="badge bg-{{ $result->is_verified ? 'success' : 'warning' }}">
                <i class="ti ti-{{ $result->is_verified ? 'shield-check' : 'clock' }} me-1"></i>
                {{ $result->is_verified ? __('lab.verified_badge') : __('lab.pending_verification') }}
            </span>
        </div>
        <div class="result-preview-meta">
            <div><small>{{ __('lab.patient_label') }}</small><strong>{{ $patient?->full_name ?? $labRequest->external_party_name ?? '—' }}</strong></div>
            <div><small>{{ __('common.patient_no') }}</small><strong>{{ $patient?->patient_number ?? __('lab.walk_in') }}</strong></div>
            <div><small>{{ __('lab.investigation_col') }}</small><strong>{{ $item->display_name }}</strong></div>
            <div><small>{{ __('common.date') }}</small><strong>{{ $result->performed_at?->format('d M Y H:i') ?? '—' }}</strong></div>
        </div>
    </div>

    @include('lab.partials.report-result', ['item' => $item])

    <div class="result-preview-signoff">
        <div>
            <small class="text-muted d-block">{{ __('lab.performed_by_label') }}</small>
            <strong>{{ $result->performedBy?->name ?? '—' }}</strong>
            <div class="small text-muted">{{ $result->performed_at?->format('d M Y H:i') ?? '—' }}</div>
        </div>
        <div>
            <small class="text-muted d-block">{{ __('lab.verified_by_label') }}</small>
            <strong>{{ $result->verifiedBy?->name ?? '—' }}</strong>
            <div class="small text-muted">{{ $result->verified_at?->format('d M Y H:i') ?? __('lab.awaiting_verification') }}</div>
        </div>
    </div>

    @if($result->is_verified)
        <div class="mt-3 text-end">
            <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-primary">
                <i class="ti ti-printer me-1"></i>{{ __('lab.print_report_btn') }}
            </a>
        </div>
    @endif
</div>
@endif

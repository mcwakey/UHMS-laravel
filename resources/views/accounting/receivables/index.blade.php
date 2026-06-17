@extends('layouts.app')
@section('title', __('receivables.receivable_workbench'))

@php
    $money = fn ($n) => 'GHS '.number_format((float) $n, 2);
    $sourceType = \App\Models\InvoiceReceivable::class;
@endphp

@section('content')
<x-page-header :title="__('receivables.receivable_workbench')" icon="ti-report-money" :description="__('receivables.workbench_description')" />

<form method="GET" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">{{ __('receivables.as_of') }}</label>
            <input type="date" name="as_of" value="{{ $filters['as_of'] ?? now()->toDateString() }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('receivables.payer_type') }}</label>
            <select name="payer_type" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                @foreach(['patient','insurance','sponsor','corporate'] as $type)
                    <option value="{{ $type }}" @selected(($filters['payer_type'] ?? '') === $type)>{{ __('receivables.'.$type.'_receivables') }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('common.filter') }}</button></div>
    </div>
</form>

<div class="row g-2 mb-3">
    @foreach([
        'total_ar' => __('receivables.total_ar'),
        'current' => __('receivables.current'),
        'b1_30' => __('receivables.aging_1_30'),
        'b31_60' => __('receivables.aging_31_60'),
        'b61_90' => __('receivables.aging_61_90'),
        'over_90' => __('receivables.over_90'),
        'disputed_amount' => __('receivables.disputed_balance'),
        'promised_amount' => __('receivables.promised_balance'),
    ] as $key => $label)
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">{{ $label }}</small>
                    <strong>{{ $money($metrics[$key] ?? 0) }}</strong>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.aged_receivables') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>{{ __('receivables.aging_bucket') }}</th><th class="text-end">{{ __('common.count') }}</th><th class="text-end">{{ __('common.total') }}</th></tr></thead>
                    <tbody>
                        @foreach($aging['buckets'] as $bucket)
                            <tr><td>{{ $bucket['label'] }}</td><td class="text-end">{{ $bucket['count'] }}</td><td class="text-end">{{ $money($bucket['total']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.payer_balance') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>{{ __('receivables.payer_type') }}</th><th>{{ __('receivables.payer') }}</th><th class="text-end">{{ __('common.balance') }}</th></tr></thead>
                    <tbody>
                        @forelse($payer_balances as $balance)
                            <tr><td>{{ ucfirst($balance['payer_type']) }}</td><td>{{ $balance['payer_name'] }}</td><td class="text-end">{{ $money($balance['balance']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ __('receivables.no_receivables') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.open_receivables') }}</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>{{ __('billing.invoice') }}</th><th>{{ __('receivables.payer') }}</th><th>{{ __('receivables.aging_bucket') }}</th><th class="text-end">{{ __('common.balance') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse($receivables as $receivable)
                            <tr>
                                <td>{{ $receivable->invoice?->invoice_number }}</td>
                                <td><span class="badge bg-{{ $receivable->payerBadgeColor() }}">{{ $receivable->payer_type }}</span> {{ $receivable->payerName() }}</td>
                                <td>{{ $receivable->due_date?->format('d M Y') ?? '-' }}</td>
                                <td class="text-end">{{ $money($receivable->balance) }}</td>
                                <td class="text-end">
                                    @can('receivables.cases.manage')
                                        <form method="POST" action="{{ route('admin.accounting.receivables.cases.store') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="invoice_receivable_id" value="{{ $receivable->id }}">
                                            <button class="btn btn-sm btn-outline-primary">{{ __('receivables.open_case') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('receivables.no_receivables') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.payer_statement') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounting.receivables.statements.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="payer_type" class="form-select" required>@foreach(['patient','insurance','sponsor','corporate'] as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div>
                    <div class="col-12"><input type="number" name="payer_id" class="form-control" placeholder="{{ __('receivables.payer_id_optional') }}"></div>
                    <div class="col-6"><input type="date" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}" class="form-control" required></div>
                    <div class="col-6"><input type="date" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}" class="form-control" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">{{ __('receivables.generate_statement') }}</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.receivable_cases') }}</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>{{ __('receivables.receivable_case') }}</th><th>{{ __('receivables.payer') }}</th><th>{{ __('common.status') }}</th><th class="text-end">{{ __('common.balance') }}</th><th>{{ __('receivables.actions') }}</th></tr></thead>
            <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td><strong>{{ $case->case_number }}</strong><br><small class="text-muted">{{ $case->case_type }} / {{ $case->priority }}</small></td>
                        <td>{{ $case->payer_name_snapshot }}<br><small class="text-muted">{{ $case->assignee?->name ?? __('receivables.unassigned') }}</small></td>
                        <td><span class="badge bg-secondary">{{ $case->status }}</span></td>
                        <td class="text-end">{{ $money($case->total_outstanding_amount) }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @can('receivables.cases.assign')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.assign', $case) }}" class="d-flex gap-1">
                                        @csrf
                                        <select name="assigned_to" class="form-select form-select-sm">@foreach($collectors as $collector)<option value="{{ $collector->id }}">{{ $collector->name }}</option>@endforeach</select>
                                        <button class="btn btn-sm btn-outline-secondary">{{ __('receivables.assign') }}</button>
                                    </form>
                                @endcan
                                @can('receivables.followups.create')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.followups.store', $case) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="followup_type" value="internal_note">
                                        <input type="hidden" name="followup_date" value="{{ now()->toDateString() }}">
                                        <input type="hidden" name="outcome" value="other">
                                        <input name="summary" class="form-control form-control-sm" placeholder="{{ __('receivables.collection_followup') }}" required>
                                        <button class="btn btn-sm btn-outline-primary">{{ __('common.add') }}</button>
                                    </form>
                                @endcan
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @can('receivables.promises.manage')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.promises.store', $case) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="promise_date" value="{{ now()->toDateString() }}">
                                        <input type="date" name="expected_payment_date" class="form-control form-control-sm" required>
                                        <input name="promised_amount" type="number" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="{{ __('receivables.payment_promise') }}" required>
                                        <button class="btn btn-sm btn-outline-success">{{ __('common.save') }}</button>
                                    </form>
                                @endcan
                                @can('receivables.disputes.manage')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.disputes.store', $case) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="source_type" value="{{ $sourceType }}">
                                        <input type="hidden" name="source_id" value="{{ $case->items->first()?->source_id }}">
                                        <input name="dispute_reason" class="form-control form-control-sm" placeholder="{{ __('receivables.receivable_dispute') }}" required>
                                        <input name="disputed_amount" type="number" step="0.01" min="0.01" class="form-control form-control-sm" placeholder="{{ __('common.amount') }}" required>
                                        <button class="btn btn-sm btn-outline-warning">{{ __('common.save') }}</button>
                                    </form>
                                @endcan
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @can('receivables.dunning.generate')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.dunning.store', $case) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="notice_level" value="friendly_reminder">
                                        <input type="hidden" name="notice_date" value="{{ now()->toDateString() }}">
                                        <button class="btn btn-sm btn-outline-dark">{{ __('receivables.dunning_notice') }}</button>
                                    </form>
                                @endcan
                                @can('receivables.recommendations.writeoff')
                                    <form method="POST" action="{{ route('admin.accounting.receivables.cases.recommend-writeoff', $case) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="source_type" value="{{ $sourceType }}">
                                        <input type="hidden" name="source_id" value="{{ $case->items->first()?->source_id }}">
                                        <input type="hidden" name="recommended_amount" value="{{ $case->total_outstanding_amount }}">
                                        <input type="hidden" name="reason" value="Collector recommendation">
                                        <button class="btn btn-sm btn-outline-danger">{{ __('receivables.writeoff_recommendation') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">{{ __('receivables.no_cases') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('receivables.statement_run') }}</h5></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>{{ __('receivables.statement_run') }}</th><th>{{ __('receivables.payer') }}</th><th class="text-end">{{ __('common.balance') }}</th><th>{{ __('common.status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($statements as $statement)
                    <tr>
                        <td>{{ $statement->statement_number }}</td>
                        <td>{{ $statement->payer_name_snapshot }}</td>
                        <td class="text-end">{{ $money($statement->closing_balance) }}</td>
                        <td>{{ $statement->status }}</td>
                        <td class="text-end">@can('receivables.statements.approve')<form method="POST" action="{{ route('admin.accounting.receivables.statements.approve', $statement) }}">@csrf<button class="btn btn-sm btn-success">{{ __('common.approve') }}</button></form>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">{{ __('receivables.no_statements') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', __('accounting.bank_reconciliation'))

@section('content')
@php
    $editable = $reconciliation->isEditable();
    $user = auth()->user();
    $diff = (float) $reconciliation->difference;
    $matchByLine = $reconciliation->matches->groupBy('bank_statement_line_id');
@endphp
<x-page-header :title="$reconciliation->bankAccount->name . ' · ' . $reconciliation->period_start->format('d M Y') . ' – ' . $reconciliation->period_end->format('d M Y')" icon="ti-arrows-diff">
    <a href="{{ route('admin.accounting.bank.reconciliations.statement', $reconciliation) }}" class="btn btn-outline-secondary"><i class="ti ti-report me-1"></i>{{ __('accounting.reconciliation_statement') }}</a>
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('accounting.reconciliation_statement') }}</h6>
                <span class="badge bg-{{ ['approved'=>'success','reversed'=>'dark','reopened'=>'warning'][$reconciliation->status] ?? 'info' }}">{{ __('statuses.default.' . $reconciliation->status) }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-7 fw-normal text-muted">{{ __('accounting.closing_statement_balance') }}</dt><dd class="col-5 text-end">&#8373;{{ number_format($reconciliation->statement_closing_balance, 2) }}</dd>
                    <dt class="col-7 fw-normal text-muted">+ {{ __('accounting.outstanding_deposits') }}</dt><dd class="col-5 text-end">&#8373;{{ number_format($reconciliation->outstanding_deposits_total, 2) }}</dd>
                    <dt class="col-7 fw-normal text-muted">− {{ __('accounting.outstanding_withdrawals') }}</dt><dd class="col-5 text-end">&#8373;{{ number_format($reconciliation->outstanding_withdrawals_total, 2) }}</dd>
                    <dt class="col-7 fw-normal text-muted">± {{ __('accounting.adjustments') }}</dt><dd class="col-5 text-end">&#8373;{{ number_format($reconciliation->adjustments_total, 2) }}</dd>
                    <dt class="col-7 fw-semibold border-top pt-2">{{ __('accounting.adjusted_statement_balance') }}</dt><dd class="col-5 text-end fw-semibold border-top pt-2">&#8373;{{ number_format($reconciliation->statement_closing_balance + $reconciliation->outstanding_deposits_total - $reconciliation->outstanding_withdrawals_total + $reconciliation->adjustments_total, 2) }}</dd>
                    <dt class="col-7 fw-normal text-muted">{{ __('accounting.book_closing_balance') }}</dt><dd class="col-5 text-end">&#8373;{{ number_format($reconciliation->book_closing_balance, 2) }}</dd>
                    <dt class="col-7 fw-bold {{ abs($diff) > 0.001 ? 'text-danger' : 'text-success' }}">{{ __('accounting.difference') }}</dt><dd class="col-5 text-end fw-bold {{ abs($diff) > 0.001 ? 'text-danger' : 'text-success' }}">&#8373;{{ number_format($diff, 2) }}</dd>
                </dl>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                @if(in_array($reconciliation->status, ['prepared','reopened']))
                    @can('accounting.bank_reconciliation.approve')
                    <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.approve', $reconciliation) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        @if(abs($diff) > 0.001 && ($user?->can('accounting.bank_reconciliation.reverse')))
                            <div class="form-check"><input type="checkbox" class="form-check-input" name="override" value="1" id="ov"><label class="form-check-label small" for="ov">{{ __('common.override') ?? 'Override' }}</label></div>
                        @endif
                        <button class="btn btn-success btn-sm" @disabled(abs($diff) > 0.001 && !$user?->can('accounting.bank_reconciliation.reverse'))><i class="ti ti-check me-1"></i>{{ __('accounting.approve_reconciliation') }}</button>
                    </form>
                    @endcan
                @endif
                @if($reconciliation->status === 'approved')
                    @can('accounting.bank_reconciliation.reopen')
                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#reopenModal"><i class="ti ti-lock-open me-1"></i>{{ __('accounting.reopen_reconciliation') }}</button>
                    @endcan
                @endif
                @if(in_array($reconciliation->status, ['approved','reopened']))
                    @can('accounting.bank_reconciliation.reverse')
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reverseModal"><i class="ti ti-arrow-back-up me-1"></i>{{ __('accounting.reverse_reconciliation') }}</button>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Adjustments --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ __('accounting.adjustments') }}</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light"><tr><th>{{ __('common.type') }}</th><th class="text-end">{{ __('common.amount') }}</th><th>{{ __('common.status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($reconciliation->adjustments as $adj)
                            <tr>
                                <td>{{ __('accounting.adjustment_type_' . $adj->type) }}<div class="small text-muted">{{ $adj->description }}</div></td>
                                <td class="text-end">&#8373;{{ number_format($adj->amount, 2) }}</td>
                                <td><span class="badge bg-{{ ['posted'=>'success','approved'=>'info','rejected'=>'danger'][$adj->status] ?? 'secondary' }}">{{ __('statuses.default.' . $adj->status) }}</span></td>
                                <td class="text-end">
                                    @if($adj->status === 'proposed')@can('accounting.bank_adjustments.approve')
                                    <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.adjustments.approve', [$reconciliation, $adj]) }}" class="d-inline">@csrf<button class="btn btn-xs btn-outline-info" title="{{ __('accounting.approve_adjustment') }}"><i class="ti ti-check"></i></button></form>
                                    @endcan @endif
                                    @if($adj->status === 'approved')@can('accounting.bank_adjustments.post')
                                    <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.adjustments.post', [$reconciliation, $adj]) }}" class="d-inline">@csrf<button class="btn btn-xs btn-outline-success" title="{{ __('accounting.post_adjustment') }}"><i class="ti ti-arrow-up-right"></i></button></form>
                                    @endcan @endif
                                    @if($adj->journalEntry)<a href="{{ route('admin.accounting.journals.show', $adj->journalEntry) }}" class="small">{{ $adj->journalEntry->journal_number }}</a>@endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-2">{{ __('common.no_records') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($editable)@can('accounting.bank_adjustments.propose')
                <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.adjustments.store', $reconciliation) }}" class="border-top pt-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-6"><select name="type" class="form-select form-select-sm">@foreach(\App\Models\BankReconciliationAdjustment::TYPES as $t)<option value="{{ $t }}">{{ __('accounting.adjustment_type_' . $t) }}</option>@endforeach</select></div>
                        <div class="col-6"><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="{{ __('common.amount') }}" required></div>
                        <div class="col-12"><select name="account_id" class="form-select form-select-sm"><option value="">{{ __('accounting.contra_account') }}</option>@foreach($contraAccounts as $ca)<option value="{{ $ca->id }}">{{ $ca->code }} — {{ $ca->name }}</option>@endforeach</select></div>
                        <div class="col-12"><input type="text" name="description" class="form-control form-control-sm" placeholder="{{ __('accounting.description') }}"></div>
                        <div class="col-12 text-end"><button class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('accounting.propose_adjustment') }}</button></div>
                    </div>
                </form>
                @endcan @endif
            </div>
        </div>
    </div>

    {{-- Statement lines + matching --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">{{ __('accounting.statement_lines') }}</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light"><tr><th>{{ __('accounting.transaction_date') }}</th><th>{{ __('accounting.description') }}</th><th class="text-end">{{ __('common.amount') }}</th><th>{{ __('accounting.match_status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($lines as $line)
                            <tr>
                                <td>{{ $line->transaction_date->format('d M Y') }}<div class="small text-muted">{{ $line->reference }}</div></td>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ $line->credit_amount > 0 ? '+' : '−' }}&#8373;{{ number_format($line->grossAmount(), 2) }}</td>
                                <td><span class="badge bg-{{ $line->match_status === 'matched' ? 'success' : ($line->match_status === 'partially_matched' ? 'info' : 'secondary') }}">{{ __('statuses.default.' . $line->match_status) }}</span></td>
                                <td class="text-end">
                                    @if($editable && $line->match_status !== 'matched')@can('accounting.bank_reconciliation.match')
                                    <button class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#m{{ $line->id }}"><i class="ti ti-link"></i></button>
                                    @endcan @endif
                                </td>
                            </tr>
                            @if($editable && $line->match_status !== 'matched')
                            <tr class="collapse" id="m{{ $line->id }}"><td colspan="5" class="bg-light">
                                @can('accounting.bank_reconciliation.match')
                                <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.match', [$reconciliation, $line]) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="matchable_type" value="{{ \App\Models\JournalEntryLine::class }}">
                                    <input type="hidden" name="match_method" value="manual">
                                    <div class="col-md-5"><label class="form-label small mb-0">{{ __('accounting.manual_match') }} (Journal line ID)</label><input type="number" name="matchable_id" class="form-control form-control-sm" required></div>
                                    <div class="col-md-4"><label class="form-label small mb-0">{{ __('accounting.matched_amount') }}</label><input type="number" step="0.01" min="0.01" max="{{ $line->remainingToMatch() }}" name="matched_amount" value="{{ number_format($line->remainingToMatch(), 2, '.', '') }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100"><i class="ti ti-check me-1"></i>{{ __('accounting.confirm_match') }}</button></div>
                                </form>
                                @endcan
                            </td></tr>
                            @endif
                            @php $lineMatches = $matchByLine->get($line->id, collect()); @endphp
                            @foreach($lineMatches as $lm)
                            <tr class="small"><td></td><td colspan="2" class="text-muted"><i class="ti ti-link me-1"></i>{{ class_basename($lm->matchable_type) }} #{{ $lm->matchable_id }} · {{ $lm->match_method }}</td><td class="text-end">&#8373;{{ number_format($lm->matched_amount, 2) }}</td><td class="text-end">
                                @if($editable)@can('accounting.bank_reconciliation.match')
                                <form method="POST" action="{{ route('admin.accounting.bank.reconciliations.unmatch', [$reconciliation, $lm]) }}" class="d-inline">@csrf<button class="btn btn-xs btn-outline-danger" title="{{ __('accounting.unmatch') }}"><i class="ti ti-x"></i></button></form>
                                @endcan @endif
                            </td></tr>
                            @endforeach
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('common.no_records') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reopen / reverse modals --}}
@can('accounting.bank_reconciliation.reopen')
<div class="modal fade" id="reopenModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.accounting.bank.reconciliations.reopen', $reconciliation) }}"><div class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('accounting.reopen_reconciliation') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-warning">{{ __('accounting.reopen_reconciliation') }}</button></div>
</div></form></div></div>
@endcan
@can('accounting.bank_reconciliation.reverse')
<div class="modal fade" id="reverseModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.accounting.bank.reconciliations.reverse', $reconciliation) }}"><div class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('accounting.reverse_reconciliation') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-danger">{{ __('accounting.reverse_reconciliation') }}</button></div>
</div></form></div></div>
@endcan
@endsection

@extends('layouts.app')

@section('title', 'Payroll Accounting Posting')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Payroll Accounting Posting</h4>
        <div class="text-muted small">Post approved payroll, recognise PAYE/SSNIT liabilities, and settle net salary payable.</div>
    </div>
    <form method="GET" action="{{ route('admin.accounting.payroll-posting.index') }}" class="d-flex gap-2">
        <input type="month" name="pay_period" class="form-control" value="{{ $payPeriod }}">
        <button class="btn btn-outline-primary">Filter</button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Action blocked.</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@forelse($runs as $run)
    @php
        $preview = $previews[$run->id];
        $outstanding = max(0, $preview['net_pay'] - (float) $run->settled_amount);
        $postedPaye = (float) $run->statutorySettlements->where('liability_type', 'paye')->where('status', 'posted')->sum('amount');
        $postedPension = (float) $run->statutorySettlements->where('liability_type', 'pension')->where('status', 'posted')->sum('amount');
        $payeOutstanding = max(0, $preview['paye'] - $postedPaye);
        $pensionOutstanding = max(0, $preview['credits']['pension_payable'] - $postedPension);
    @endphp
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <div class="flex-grow-1">
                <h5 class="mb-0">{{ $run->pay_period }} payroll</h5>
                <div class="text-muted small">
                    {{ $run->records_count }} employee(s) · payroll status {{ ucfirst(str_replace('_', ' ', $run->status)) }} · accounting {{ ucfirst($run->accounting_status ?? 'pending') }}
                </div>
            </div>
            @if($run->journalEntry)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.accounting.journals.show', $run->journalEntry) }}">View Journal</a>
            @endif
            @can('accounting.payroll_posting.post')
                @if(in_array($run->status, ['approved', 'posted'], true) && $run->accounting_status !== 'posted')
                    <form method="POST" action="{{ route('admin.accounting.payroll-posting.post', $run) }}">
                        @csrf
                        <button class="btn btn-sm btn-primary">Post Payroll</button>
                    </form>
                @endif
            @endcan
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Line</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Salary expense</td><td class="text-end">{{ number_format($preview['debits']['payroll_expense'], 2) }}</td><td></td></tr>
                                <tr><td>Employer pension expense</td><td class="text-end">{{ number_format($preview['debits']['employer_pension_expense'], 2) }}</td><td></td></tr>
                                <tr><td>Net salary payable</td><td></td><td class="text-end">{{ number_format($preview['credits']['payroll_payable'], 2) }}</td></tr>
                                <tr><td>PAYE payable</td><td></td><td class="text-end">{{ number_format($preview['credits']['paye_payable'], 2) }}</td></tr>
                                <tr><td>Pension / SSNIT payable</td><td></td><td class="text-end">{{ number_format($preview['credits']['pension_payable'], 2) }}</td></tr>
                                <tr><td>Other payroll deductions payable</td><td></td><td class="text-end">{{ number_format($preview['credits']['other_deductions_payable'], 2) }}</td></tr>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr><td>Total</td><td class="text-end">{{ number_format($preview['total_debit'], 2) }}</td><td class="text-end">{{ number_format($preview['total_credit'], 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between"><span>Net salary</span><strong>{{ number_format($preview['net_pay'], 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Settled</span><strong>{{ number_format((float) $run->settled_amount, 2) }}</strong></div>
                        <div class="d-flex justify-content-between mb-3"><span>Outstanding</span><strong>{{ number_format($outstanding, 2) }}</strong></div>

                        @can('accounting.payroll_posting.settle')
                            @if($run->accounting_status === 'posted' && $outstanding > 0)
                                <form method="POST" action="{{ route('admin.accounting.payroll-posting.settle', $run) }}" class="d-grid gap-2">
                                    @csrf
                                    <input type="date" name="settlement_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                    <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" max="{{ $outstanding }}" value="{{ $outstanding }}" required>
                                    <select name="payment_account_id" class="form-select form-select-sm">
                                        @foreach($paymentAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->display_name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Settlement note">
                                    <button class="btn btn-sm btn-success">Post Salary Settlement</button>
                                </form>
                            @endif
                        @endcan

                        @can('accounting.payroll_posting.reverse')
                            @if($run->accounting_status === 'posted' && $run->settlements->where('status', 'posted')->isEmpty())
                                <form method="POST" action="{{ route('admin.accounting.payroll-posting.reverse', $run) }}" class="mt-2 d-grid gap-2">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reversal reason" required>
                                    <button class="btn btn-sm btn-outline-danger">Reverse Payroll Accrual</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @if($run->settlements->isNotEmpty())
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Settlement</th>
                                <th>Date</th>
                                <th>Account</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($run->settlements as $settlement)
                                <tr>
                                    <td>{{ $settlement->settlement_number }}</td>
                                    <td>{{ $settlement->settlement_date?->toDateString() }}</td>
                                    <td>{{ $settlement->paymentAccount?->display_name }}</td>
                                    <td class="text-end">{{ number_format((float) $settlement->amount, 2) }}</td>
                                    <td>{{ ucfirst($settlement->status) }}</td>
                                    <td class="text-end">
                                        @if($settlement->journalEntry)
                                            <a href="{{ route('admin.accounting.journals.show', $settlement->journalEntry) }}" class="btn btn-sm btn-outline-secondary">Journal</a>
                                        @endif
                                        @can('accounting.payroll_posting.reverse')
                                            @if($settlement->status === 'posted')
                                                <form method="POST" action="{{ route('admin.accounting.payroll-posting.settlements.reverse', $settlement) }}" class="d-inline-flex gap-1">
                                                    @csrf
                                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required>
                                                    <button class="btn btn-sm btn-outline-danger">Reverse</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="row g-3 mt-1">
                @foreach([
                    'paye' => ['label' => 'PAYE', 'liability' => $preview['paye'], 'settled' => $postedPaye, 'outstanding' => $payeOutstanding],
                    'pension' => ['label' => 'Pension / SSNIT', 'liability' => $preview['credits']['pension_payable'], 'settled' => $postedPension, 'outstanding' => $pensionOutstanding],
                ] as $type => $liability)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold mb-2">{{ $liability['label'] }} Statutory Settlement</h6>
                            <div class="d-flex justify-content-between"><span>Liability</span><strong>{{ number_format($liability['liability'], 2) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Settled</span><strong>{{ number_format($liability['settled'], 2) }}</strong></div>
                            <div class="d-flex justify-content-between mb-3"><span>Outstanding</span><strong>{{ number_format($liability['outstanding'], 2) }}</strong></div>

                            @can('accounting.payroll_posting.settle')
                                @if($run->accounting_status === 'posted' && $liability['outstanding'] > 0)
                                    <form method="POST" action="{{ route('admin.accounting.payroll-posting.statutory-settle', $run) }}" class="d-grid gap-2">
                                        @csrf
                                        <input type="hidden" name="liability_type" value="{{ $type }}">
                                        <input type="date" name="settlement_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                        <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" max="{{ $liability['outstanding'] }}" value="{{ $liability['outstanding'] }}" required>
                                        <select name="payment_account_id" class="form-select form-select-sm">
                                            @foreach($paymentAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->display_name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ $liability['label'] }} remittance reference">
                                        <button class="btn btn-sm btn-outline-success">Post {{ $liability['label'] }} Settlement</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            @if($run->statutorySettlements->isNotEmpty())
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Statutory Settlement</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Account</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($run->statutorySettlements as $settlement)
                                <tr>
                                    <td>{{ $settlement->settlement_number }}</td>
                                    <td>{{ $settlement->liability_type === 'paye' ? 'PAYE' : 'Pension / SSNIT' }}</td>
                                    <td>{{ $settlement->settlement_date?->toDateString() }}</td>
                                    <td>{{ $settlement->paymentAccount?->display_name }}</td>
                                    <td class="text-end">{{ number_format((float) $settlement->amount, 2) }}</td>
                                    <td>{{ ucfirst($settlement->status) }}</td>
                                    <td class="text-end">
                                        @if($settlement->journalEntry)
                                            <a href="{{ route('admin.accounting.journals.show', $settlement->journalEntry) }}" class="btn btn-sm btn-outline-secondary">Journal</a>
                                        @endif
                                        @can('accounting.payroll_posting.reverse')
                                            @if($settlement->status === 'posted')
                                                <form method="POST" action="{{ route('admin.accounting.payroll-posting.statutory-settlements.reverse', $settlement) }}" class="d-inline-flex gap-1">
                                                    @csrf
                                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required>
                                                    <button class="btn btn-sm btn-outline-danger">Reverse</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="card"><div class="card-body text-center text-muted">No payroll runs found.</div></div>
@endforelse

{{ $runs->withQueryString()->links() }}
@endsection

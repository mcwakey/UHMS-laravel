@extends('layouts.app')
@section('title', 'Cashier Handover')

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Cashier Handover</h4>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Current Shift -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card border-{{ $openShift ? 'success' : 'secondary' }}">
            <div class="card-header bg-{{ $openShift ? 'success' : 'secondary' }} bg-opacity-10">
                <h5 class="card-title mb-0">
                    <i class="ti ti-clock me-1"></i>
                    {{ $openShift ? 'Current Shift' : 'No Active Shift' }}
                </h5>
            </div>
            <div class="card-body">
                @if($openShift)
                    <table class="table table-borderless table-sm mb-3">
                        <tr>
                            <td class="text-muted" style="width:40%;">Started</td>
                            <td class="fw-medium">{{ $openShift->started_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Opening Balance</td>
                            <td class="fw-medium">GH₵ {{ number_format($openShift->opening_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Duration</td>
                            <td>{{ $openShift->started_at->diffForHumans(now(), true) }}</td>
                        </tr>
                    </table>

                    <form method="POST" action="{{ route('admin.accounts.handover.close', $openShift) }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Actual Closing Balance (GH₵) <span class="text-danger">*</span></label>
                                <input type="number" name="actual_closing" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Shift notes...">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Close this shift?')">
                            <i class="ti ti-clock-off me-1"></i>Close Shift
                        </button>
                    </form>
                @else
                    <p class="text-muted mb-3">Start a new shift to track your cash collections.</p>
                    <form method="POST" action="{{ route('admin.accounts.handover.open') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Opening Balance (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" name="opening_balance" class="form-control" step="0.01" min="0" value="0" required style="max-width:250px;">
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-clock-play me-1"></i>Open Shift
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Shift History -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Shift History</h5>
        <form method="GET" action="{{ route('admin.accounts.handover.index') }}" class="d-flex gap-2">
            <label class="visually-hidden" for="shiftStatusFilter">Status</label>
            <select id="shiftStatusFilter" name="status" class="form-select form-select-sm" style="width:120px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Expected</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Variance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                    <tr>
                        <td>{{ $shift->shift_date->format('d M Y') }}</td>
                        <td class="fw-medium">{{ $shift->user->name }}</td>
                        <td>{{ $shift->started_at->format('H:i') }}</td>
                        <td>{{ $shift->ended_at?->format('H:i') ?? '-' }}</td>
                        <td class="text-end">GH₵ {{ number_format($shift->opening_balance, 2) }}</td>
                        <td class="text-end">{{ $shift->expected_closing !== null ? 'GH₵ ' . number_format($shift->expected_closing, 2) : '-' }}</td>
                        <td class="text-end">{{ $shift->actual_closing !== null ? 'GH₵ ' . number_format($shift->actual_closing, 2) : '-' }}</td>
                        <td class="text-end">
                            @if($shift->variance !== null)
                                <span class="fw-medium text-{{ $shift->variance >= 0 ? 'success' : 'danger' }}">
                                    {{ $shift->variance >= 0 ? '+' : '' }}GH₵ {{ number_format($shift->variance, 2) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $shift->status->color() }}">{{ $shift->status->label() }}</span></td>
                        <td class="text-end">
                            @if($shift->status === \App\Enums\ShiftStatus::CLOSED)
                            @can('accounts.entries.approve')
                            <form method="POST" action="{{ route('admin.accounts.handover.verify', $shift) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('Verify this shift?')">
                                    <i class="ti ti-check"></i>
                                </button>
                            </form>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @if($shift->notes)
                    <tr>
                        <td colspan="10" class="py-1 ps-4">
                            <small class="text-muted"><i class="ti ti-note me-1"></i>{{ $shift->notes }}</small>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ti ti-clock-off fs-2 d-block mb-2"></i>
                            No shifts found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($shifts->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $shifts->withQueryString()->links() }}
</div>
@endif
@endsection

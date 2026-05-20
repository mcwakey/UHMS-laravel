@extends('layouts.app')
@section('title', 'Emergency Cases')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Emergency Cases</h1>
        <a href="{{ route('admin.emergency.cases.create') }}" class="btn btn-danger">
            <i class="ti ti-plus"></i> New ER Case
        </a>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
            <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="ER #, patient name or number"></div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(request('status')===$s->value)>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="triage" class="form-select">
                    <option value="">All triage</option>
                    @foreach($triages as $t)<option value="{{ $t->value }}" @selected(request('triage')===$t->value)>{{ $t->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="disposition" class="form-select">
                    <option value="">All dispositions</option>
                    @foreach($dispositions as $d)<option value="{{ $d->value }}" @selected(request('disposition')===$d->value)>{{ $d->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.emergency.cases.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ER #</th><th>Patient</th><th>Arrival</th>
                        <th>Triage</th><th>Status</th><th>Disposition</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td><code>{{ $case->emergency_number }}</code></td>
                            <td>{{ $case->patient->full_name ?? '—' }}</td>
                            <td>{{ optional($case->arrival_time)->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($case->triage_category)
                                    <span class="badge bg-{{ $case->triage_category->color() }}">{{ $case->triage_category->label() }}</span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $case->status->label() }}</span></td>
                            <td>
                                @if($case->disposition)
                                    <span class="badge bg-{{ $case->disposition->color() }}">{{ $case->disposition->label() }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No cases.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $cases->links() }}</div>
    </div>
</div>
@endsection

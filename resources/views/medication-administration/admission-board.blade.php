@extends('layouts.app')
@section('title', 'Admission Medication Board')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Admission Medication Board</h4>
        <p class="text-muted mb-0">Due, overdue, upcoming, and completed medication tasks for admitted patients.</p>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="ward_id" class="form-select form-select-sm">
            <option value="">All wards</option>
            @foreach($wards as $ward)
                <option value="{{ $ward->id }}" @selected(($filters['ward_id'] ?? '') == $ward->id)>{{ $ward->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary btn-sm"><i class="ti ti-filter me-1"></i>Filter</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Patient</th>
                        <th>Ward / Bed</th>
                        <th class="text-center">Active Meds</th>
                        <th class="text-center">Due Now</th>
                        <th class="text-center">Overdue</th>
                        <th class="text-center">Upcoming</th>
                        <th class="text-center">Completed Today</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    @php $admission = $row['admission']; $counts = $row['counts']; @endphp
                    <tr class="{{ $counts['overdue'] > 0 ? 'table-danger' : ($counts['due_now'] > 0 ? 'table-info' : '') }}">
                        <td>
                            <div class="fw-semibold">{{ $admission->patient->full_name }}</div>
                            <small class="text-muted">{{ $admission->patient->patient_number }}</small>
                        </td>
                        <td>
                            <div>{{ $admission->bed->ward->name ?? 'Ward' }}</div>
                            <small class="text-muted">Bed {{ $admission->bed->bed_number ?? '—' }}</small>
                        </td>
                        <td class="text-center"><span class="badge bg-primary">{{ $row['active_medication_count'] }}</span></td>
                        <td class="text-center"><span class="badge bg-info">{{ $counts['due_now'] }}</span></td>
                        <td class="text-center"><span class="badge bg-danger">{{ $counts['overdue'] }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary">{{ $counts['upcoming'] }}</span></td>
                        <td class="text-center"><span class="badge bg-success">{{ $counts['completed_today'] }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-list-details me-1"></i>Open MAR
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No admitted patients with medication tasks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    setTimeout(function () {
        if (window.UhmsInertia) {
            window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
        }
    }, 60000);
</script>
@endpush

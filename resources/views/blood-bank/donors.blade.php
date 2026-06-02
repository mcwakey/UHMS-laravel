@extends('layouts.app')
@section('title', 'Blood Donors')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Donors</h4><p class="text-muted mb-0">Register and search blood donors.</p></div>
    <a href="{{ route('admin.blood-bank.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Register Donor</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.donors.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">Unknown</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Gender</label><input name="gender" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Address</label><input name="address" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Save Donor</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label">Search</label><input name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Donor number, name, phone"></div>
            <div class="col-md-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.blood-bank.donors.index') }}">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Donor #</th><th>Name</th><th>Blood Group</th><th>Phone</th><th>Status</th><th>Last Donation</th></tr></thead>
                <tbody>
                    @forelse($donors as $donor)
                        <tr><td>{{ $donor->donor_number }}</td><td>{{ $donor->full_name }}</td><td>{{ $donor->blood_group ?? 'Unknown' }}</td><td>{{ $donor->phone ?? '—' }}</td><td><span class="badge bg-secondary">{{ $donor->status }}</span></td><td>{{ $donor->last_donation_at?->format('d M Y') ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No donors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donors->hasPages())<div class="card-footer">{{ $donors->links() }}</div>@endif
</div>
@endsection

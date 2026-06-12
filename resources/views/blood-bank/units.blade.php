@extends('layouts.app')
@section('title', 'Blood Units')

@section('content')
<x-page-header title="Blood Units" description="Inventory and safety state for every unit." icon="ti-droplet">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('blood_bank.donations_link') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __('blood_bank.blood_group') }}</label><select name="blood_group" class="form-select"><option value="">{{ __('blood_bank.all') }}</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('blood_bank.component') }}</label><input name="component_type" class="form-control" value="{{ $filters['component_type'] ?? '' }}" placeholder="WHOLE_BLOOD"></div>
            <div class="col-md-3"><label class="form-label">{{ __('medication_administration.status') }}</label><select name="status" class="form-select"><option value="">{{ __('blood_bank.all') }}</option>@foreach(['QUARANTINED','AVAILABLE','RESERVED','CROSSMATCHED','ISSUED','TRANSFUSED','EXPIRED','DISCARDED'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary w-100">{{ __('common.filter') }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.blood-bank.units.index') }}">{{ __('blood_bank.clear') }}</a></div>
        </form>
    </div>
</div>

<x-data-table :paginator="$units">
    <x-slot:head>
        <tr><th>{{ __('blood_bank.unit') }}</th><th>{{ __('blood_bank.group') }}</th><th>{{ __('blood_bank.component') }}</th><th>{{ __('blood_bank.screening') }}</th><th>{{ __('medication_administration.status') }}</th><th>{{ __('blood_bank.storage') }}</th><th>{{ __('blood_bank.expiry') }}</th><th class="text-end">{{ __('blood_bank.action') }}</th></tr>
    </x-slot:head>
                    @forelse($units as $unit)
                        <tr>
                            <td class="fw-semibold">{{ $unit->unit_number }}<div class="small text-muted">{{ $unit->donation->donation_number ?? 'Manual unit' }}</div></td>
                            <td>{{ $unit->blood_group }}</td>
                            <td>{{ str_replace('_', ' ', $unit->component_type) }}</td>
                            <td><x-status-badge :status="$unit->screening_status" domain="screening" /></td>
                            <td><x-status-badge :status="$unit->status" domain="blood_unit" /></td>
                            <td>{{ $unit->storageLocation->name ?? '—' }}</td>
                            <td class="{{ $unit->expiry_date?->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $unit->expiry_date?->format('d M Y') }}</td>
                            <td class="text-end">
                                @if(!in_array($unit->status, ['ISSUED','TRANSFUSED','DISCARDED'], true))
                                    <form method="POST" action="{{ route('admin.blood-bank.units.discard', $unit) }}" class="d-inline-flex gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <input name="discard_reason" class="form-control form-control-sm" placeholder="Discard reason" required>
                                        <button class="btn btn-outline-danger btn-sm">{{ __('blood_bank.discard') }}</button>
                                    </form>
                                @else
                                    <span class="text-muted small">{{ __('blood_bank.locked') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state icon="ti-droplet-off" :title="__('blood_bank.no_blood_units')" :message="__('blood_bank.no_blood_units_message')" /></td></tr>
                    @endforelse
</x-data-table>
@endsection

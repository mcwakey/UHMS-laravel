@extends('layouts.app')
@section('title', __('maternity.postnatal_care'))
@section('content')
<x-page-header :title="__('maternity.postnatal_care')" icon="ti-heart-handshake">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.dashboard') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.postnatal_cases') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.patient') }}</th><th>{{ __('maternity.delivery_record') }}</th><th>{{ __('maternity.status') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.follow_up_date') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($cases as $case)
                    <tr>
                        <td><strong>{{ $case->mother?->full_name }}</strong><br><small class="text-muted">{{ $case->mother?->patient_number }}</small></td>
                        <td>{{ $case->deliveryRecord?->delivery_at?->format('d M Y H:i') ?? __('common.none') }}</td>
                        <td><span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span></td>
                        <td><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() }}</span></td>
                        <td>{{ $case->follow_up_date?->format('d M Y') ?? __('common.none') }}</td>
                        <td class="text-end"><a href="{{ route('admin.maternity.postnatal.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><x-empty-state icon="ti-heart-handshake" :title="__('maternity.no_postnatal_cases_yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cases->hasPages())<div class="card-footer">{{ $cases->links() }}</div>@endif
</div>
@endsection

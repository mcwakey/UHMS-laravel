@extends('layouts.app')
@section('title', __('maternity.labor_and_delivery'))
@section('content')
<x-page-header :title="__('maternity.labor_and_delivery')" icon="ti-baby-carriage" />
<div class="row g-3 mb-3">
@foreach([
    ['label' => __('maternity.active_labor_episodes'), 'value' => $overview['active_labor_episodes'], 'icon' => 'ti-activity', 'class' => 'primary'],
    ['label' => __('maternity.labor_danger_signs_flagged'), 'value' => $overview['labor_danger_signs_flagged'], 'icon' => 'ti-alert-triangle', 'class' => 'danger'],
    ['label' => __('maternity.theatre_escalation_required'), 'value' => $overview['theatre_escalation_required'], 'icon' => 'ti-building-hospital', 'class' => 'warning'],
    ['label' => __('maternity.deliveries_today'), 'value' => $overview['deliveries_today'], 'icon' => 'ti-confetti', 'class' => 'success'],
] as $card)
    <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ $card['label'] }}</small><div class="fs-3 fw-bold text-{{ $card['class'] }}">{{ $card['value'] }}</div></div></div></div>
@endforeach
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.patient') }}</th><th>{{ __('maternity.labor_stage') }}</th><th>{{ __('maternity.status') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.latest_observation') }}</th><th>{{ __('maternity.delivery_record') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($episodes as $episode)
                    <tr>
                        <td><strong>{{ $episode->patient?->full_name }}</strong><br><small class="text-muted">{{ $episode->patient?->patient_number }}</small></td>
                        <td>{{ $episode->labor_stage?->label() }}</td>
                        <td><span class="badge bg-{{ $episode->status?->color() ?? 'secondary' }}">{{ $episode->status?->label() }}</span></td>
                        <td><span class="badge badge-soft-{{ $episode->risk_level?->color() ?? 'secondary' }}">{{ $episode->risk_level?->label() ?? __('common.none') }}</span></td>
                        <td>{{ $episode->latestObservation?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</td>
                        <td>{{ $episode->latestDeliveryRecord?->status?->label() ?? __('common.none') }}</td>
                        <td class="text-end"><a href="{{ route('admin.maternity.labor.show', $episode) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-baby-carriage" :title="__('maternity.no_labor_episodes_yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($episodes->hasPages())<div class="card-footer">{{ $episodes->links() }}</div>@endif
</div>
@endsection

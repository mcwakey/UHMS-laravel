{{-- Secondary mini-KPI row. $col overrides the column classes. --}}
@if(!empty($secondary_cards))
<div class="row g-2 mb-3">
    @foreach($secondary_cards as $card)
        <div class="{{ $col ?? 'col-xl-2 col-md-4 col-6' }}">
            @include('admin.dashboards.department.partials.mini-kpi-card', ['card' => $card])
        </div>
    @endforeach
</div>
@endif

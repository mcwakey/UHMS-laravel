{{-- Primary KPI row. $col overrides the column classes. --}}
@if(!empty($primary_cards))
<div class="row g-3 mb-3">
    @foreach($primary_cards as $card)
        <div class="{{ $col ?? 'col-xl-3 col-md-6' }}">
            @include('admin.dashboards.department.partials.kpi-card', ['card' => $card, 'theme' => $theme, 'index' => $loop->index])
        </div>
    @endforeach
</div>
@endif

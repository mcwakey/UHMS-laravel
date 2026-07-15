<div class="row g-3 mb-4">
    @foreach(['waiting_for_triage' => 'warning', 'triage_in_progress' => 'info', 'vitals_incomplete' => 'danger', 'waiting_for_consultation' => 'primary', 'nursing_action_required' => 'warning', 'completed_today' => 'success'] as $key => $color)
        <div class="col-xl-2 col-md-4 col-6">
            <a class="card border shadow-sm h-100 text-decoration-none" href="{{ route('nursing.opd.queue', ['category' => $key]) }}">
                <div class="card-body"><div class="text-muted small">{{ __('nursing.metrics.'.$key) }}</div><div class="fs-2 fw-bold text-{{ $color }}">{{ $metrics[$key] }}</div></div>
            </a>
        </div>
    @endforeach
</div>

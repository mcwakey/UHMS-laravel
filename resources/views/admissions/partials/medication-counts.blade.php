{{--
    Shared medication administration count badges.
    Params:
        $counts (array)   — ['due_now','overdue','upcoming','completed_today']
        $layout (string)  — 'inline' (wrap flex) or 'grid' (4 equal columns). Default 'inline'.
--}}
@php
    $counts = $counts ?? [];
    $layout = $layout ?? 'inline';
    $items = [
        ['key' => 'due_now',         'class' => 'bg-info',      'label' => __('admissions.due_now_badge')],
        ['key' => 'overdue',         'class' => 'bg-danger',    'label' => __('admissions.overdue_badge')],
        ['key' => 'upcoming',        'class' => 'bg-secondary', 'label' => __('admissions.upcoming_badge')],
        ['key' => 'completed_today', 'class' => 'bg-success',   'label' => __('admissions.completed_today_badge')],
    ];
@endphp
@if($layout === 'grid')
<div class="row g-2">
    @foreach($items as $item)
    <div class="col-6 col-md-3"><span class="badge {{ $item['class'] }} w-100 py-2">{{ $item['label'] }}: {{ $counts[$item['key']] ?? 0 }}</span></div>
    @endforeach
</div>
@else
<div class="d-flex flex-wrap gap-2">
    @foreach($items as $item)
    <span class="badge {{ $item['class'] }}">{{ $item['label'] }}: {{ $counts[$item['key']] ?? 0 }}</span>
    @endforeach
</div>
@endif

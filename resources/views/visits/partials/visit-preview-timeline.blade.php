{{--
    Single timeline row.
    Pass a single $item array (one entry from VisitPreviewService::build()).

    $item keys:
        date_label, time_label, title, description,
        entered_by, department, badge, badge_class, details, source_type
--}}
<div class="d-flex align-items-start">
    <p class="text-dark me-4 mb-0 timeline-date flex-shrink-0">
        {{ $item['date_label'] ?? '—' }}
        @if(!empty($item['time_label']))
            <small class="d-block text-muted">{{ $item['time_label'] }}</small>
        @endif
    </p>

    <div class="border-start ps-4 py-4 border-circle position-relative w-100">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <p class="text-dark fw-semibold mb-0">{{ $item['title'] }}</p>

            @if(!empty($item['badge']))
                <span class="badge {{ $item['badge_class'] ?? 'bg-secondary' }}">
                    {{ $item['badge'] }}
                </span>
            @endif
        </div>

        <p class="mb-1">{{ $item['description'] }}</p>

        @if(!empty($item['details']))
            <div class="d-flex flex-wrap gap-3 mb-1">
                @foreach($item['details'] as $key => $val)
                    @if($val)
                        <small><span class="text-muted">{{ $key }}:</span> {{ $val }}</small>
                    @endif
                @endforeach
            </div>
        @endif

        <small class="text-muted">
            @if(!empty($item['entered_by']))
                <i class="ti ti-user me-1"></i>{{ $item['entered_by'] }}
            @endif
            @if(!empty($item['department']))
                &nbsp;·&nbsp;<i class="ti ti-building-hospital me-1"></i>{{ $item['department'] }}
            @endif
        </small>
    </div>
</div>

{{-- A single dashboard work queue. Expects: $queue = {title, icon, rows[], view_all, empty} --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti {{ $queue['icon'] ?? 'ti-list' }} me-1"></i>{{ $queue['title'] }}</h6>
        @if(!empty($queue['view_all']))
            <a href="{{ $queue['view_all'] }}" class="btn btn-sm btn-outline-secondary">{{ __('dashboards.view_all') }}</a>
        @endif
    </div>
    <div class="card-body p-0">
        @if(!empty($queue['rows']))
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <tbody>
                @foreach($queue['rows'] as $row)
                    <tr @if(!empty($row['url'])) role="button" onclick="window.location='{{ $row['url'] }}'" @endif>
                        <td>
                            <div class="fw-medium">{{ $row['label'] }}</div>
                            @if(!empty($row['meta']))<small class="text-muted">{{ $row['meta'] }}</small>@endif
                        </td>
                        <td class="text-end">
                            @if(isset($row['badge']))
                                <span class="badge bg-{{ $row['badge_variant'] ?? 'secondary' }}-subtle text-{{ $row['badge_variant'] ?? 'secondary' }}">{{ $row['badge'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-3">
                <x-empty-state icon="ti-inbox" :title="$queue['empty'] ?? 'Nothing here'" message="" />
            </div>
        @endif
    </div>
</div>

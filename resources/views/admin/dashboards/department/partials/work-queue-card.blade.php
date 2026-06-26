<div class="card shadow-sm mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="ti {{ $queue['icon'] ?? 'ti-list' }} me-1"></i>{{ $queue['title'] ?? __('dashboards.department.department_work_queue') }}</h5>
    </div>
    <div class="card-body p-0">
        @if(empty($queue['rows']))
            @include('admin.dashboards.department.partials.empty-card', ['message' => $queue['empty'] ?? __('dashboards.department.no_department_activity'), 'icon' => $theme['empty_state_icon'] ?? 'ti-layout-dashboard'])
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <tbody>
                        @foreach($queue['rows'] as $row)
                        <tr>
                            <td>
                                @if(!empty($row['url']))
                                    <a href="{{ $row['url'] }}" class="fw-medium">{{ $row['label'] }}</a>
                                @else
                                    <span class="fw-medium">{{ $row['label'] }}</span>
                                @endif
                                @if(!empty($row['meta']))<div class="small text-muted">{{ $row['meta'] }}</div>@endif
                            </td>
                            <td class="text-end">
                                @if(!empty($row['badge']))<span class="badge bg-{{ $row['badge_variant'] ?? 'secondary' }}">{{ $row['badge'] }}</span>@endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

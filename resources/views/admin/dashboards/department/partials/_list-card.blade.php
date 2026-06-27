{{-- Reusable rich list card (Preclinic-style): avatar + title/meta left, badge right.
     Expects $list = ['title','icon','rows'=>[['label','meta','badge','badge_variant','url']],'empty','view_all_route']. --}}
@php
    $list = $list ?? ($queue ?? []);
    $accent = $theme['accent_class'] ?? 'secondary';
    $rows = $list['rows'] ?? [];
@endphp
<div class="card shadow-sm mb-3 h-100">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti {{ $list['icon'] ?? 'ti-list' }} me-1"></i>{{ $list['title'] ?? '' }}</h6>
        @if(!empty($list['view_all_route']))
            <a href="{{ $list['view_all_route'] }}" class="btn btn-sm btn-light">{{ __('common.view_all') }}</a>
        @endif
    </div>
    <div class="card-body p-0">
        @forelse($rows as $row)
            @php
                $label = (string) ($row['label'] ?? '');
                $initials = \Illuminate\Support\Str::of($label)->squish()->explode(' ')
                    ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                $variant = $row['badge_variant'] ?? $accent;
            @endphp
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom department-list-row">
                <span class="avatar rounded-circle bg-{{ $variant }}-subtle text-{{ $variant }} flex-shrink-0">{{ $initials !== '' ? $initials : '#' }}</span>
                <div class="flex-fill min-w-0">
                    @if(!empty($row['url']))
                        <a href="{{ $row['url'] }}" class="fw-semibold text-dark text-decoration-none d-block text-truncate">{{ $label }}</a>
                    @else
                        <span class="fw-semibold text-dark d-block text-truncate">{{ $label }}</span>
                    @endif
                    @if(!empty($row['meta']))<div class="small text-muted text-truncate">{{ $row['meta'] }}</div>@endif
                </div>
                @if(!empty($row['badge']))
                    <span class="badge bg-{{ $variant }}-subtle text-{{ $variant }} flex-shrink-0">{{ $row['badge'] }}</span>
                @endif
            </div>
        @empty
            @include('admin.dashboards.department.partials.empty-card', [
                'message' => $list['empty'] ?? __('dashboards.department.no_queue_items'),
                'icon' => $list['icon'] ?? 'ti-list',
            ])
        @endforelse
    </div>
</div>

@props(['title' => '', 'rows' => []])
<div class="card h-100">
    <div class="card-header"><h6 class="mb-0 fs-14">{{ $title }}</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <tbody>
                    @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['label'] ?: __('front_desk.none') }}</td>
                        <td class="text-end"><span class="badge badge-soft-primary">{{ $r['count'] }}</span></td>
                    </tr>
                    @empty
                    <tr><td class="text-center text-muted py-3">{{ __('front_desk.reports.no_report_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@extends('layouts.app')

@section('title', 'Permissions Dashboard')

@section('content')
<div class="page-wrapper">
    <div class="content">

        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Permissions Dashboard</h4>
                <p class="text-muted mb-0">
                    Catalogue of every permission in the system, plus the latest audit findings.
                </p>
            </div>
            <div class="d-flex gap-2">
                @can('permissions.assign')
                <form method="POST" action="{{ route('admin.permissions.refresh') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-md">
                        <i class="ti ti-refresh me-1"></i>Refresh Audit
                    </button>
                </form>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ── Audit summary ─────────────────────────────────────────── --}}
        @php
            $totals       = $report['totals'] ?? [];
            $byRisk       = $report['risk_distribution'] ?? [];
            $missing      = $report['referenced_not_in_db'] ?? [];
            $unprotected  = $report['unprotected_routes'] ?? [];
            $unused       = $report['unused_in_routes'] ?? [];
            $duplicates   = $report['possible_duplicates'] ?? [];
            $generatedAt  = $report['generated_at'] ?? null;
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Permissions</div>
                        <h3 class="fw-bold mb-0">{{ $totals['permissions_in_db'] ?? '—' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Used in Routes</div>
                        <h3 class="fw-bold mb-0">{{ $totals['used_in_routes'] ?? '—' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Admin Mutation Routes</div>
                        <h3 class="fw-bold mb-0">{{ $totals['admin_mutation_routes'] ?? '—' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Last Audit</div>
                        <h6 class="mb-0">{{ $generatedAt ? \Illuminate\Support\Carbon::parse($generatedAt)->diffForHumans() : '—' }}</h6>
                        <div class="text-muted fs-12">{{ $generatedAt }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Risk distribution + drift findings ───────────────────── --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header"><strong>Risk Distribution</strong></div>
                    <div class="card-body">
                        @foreach(['LOW','NORMAL','HIGH','CRITICAL'] as $level)
                        @php $meta = $riskLevels[$level] ?? ['label' => $level, 'color' => 'secondary']; @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                            <strong>{{ $byRisk[$level] ?? 0 }}</strong>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-warning"><strong>Drift — Code uses, DB lacks ({{ count($missing) }})</strong></div>
                    <div class="card-body" style="max-height: 240px; overflow:auto;">
                        @forelse($missing as $p)
                            <div><code>{{ $p }}</code></div>
                        @empty
                            <div class="text-success">No drift detected.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-danger"><strong>Unprotected admin mutations ({{ count($unprotected) }})</strong></div>
                    <div class="card-body" style="max-height: 240px; overflow:auto;">
                        @forelse($unprotected as $r)
                            <div class="small"><code>{{ $r }}</code></div>
                        @empty
                            <div class="text-success">All admin mutation routes are gated.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($duplicates))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header"><strong>Possible duplicate permission names ({{ count($duplicates) }})</strong></div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach($duplicates as $pair)
                        <li><code>{{ $pair[0] }}</code> ⇄ <code>{{ $pair[1] }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- ── Permission catalogue ─────────────────────────────────── --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong>Permission Catalogue</strong>
                <input type="text" id="perm-filter" class="form-control form-control-sm" style="max-width:240px;" placeholder="Filter…">
            </div>
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Module</th>
                            <th>Permission</th>
                            <th>What it allows</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($catalogue as $module => $items)
                            @foreach($items as $item)
                            @php $rm = $riskLevels[$item['risk']] ?? ['label'=>$item['risk'],'color'=>'secondary']; @endphp
                            <tr class="perm-row">
                                <td><span class="badge bg-light text-dark">{{ $module }}</span></td>
                                <td><code>{{ $item['name'] }}</code></td>
                                <td class="small text-muted">{{ $item['description'] }}</td>
                                <td><span class="badge bg-{{ $rm['color'] }}">{{ $rm['label'] }}</span></td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Simple client-side filter
    var filter = document.getElementById('perm-filter');
    if (filter) {
        filter.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.perm-row').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
            });
        });
    }
</script>
@endpush

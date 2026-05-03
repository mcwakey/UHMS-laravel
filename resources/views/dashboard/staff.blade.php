@extends('layouts.app')

@section('title', $role . ' Dashboard')

@section('content')
        <div class="uhms-page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Welcome, {{ $user->name }}</h4>
                <p class="text-muted mb-0">{{ $role }} Dashboard &middot; {{ now()->format('l, d M Y') }}</p>
            </div>
        </div>

        @if(!empty($stats))
        <div class="row g-3 mb-4">
            @foreach($stats as $label => $value)
            <div class="col-sm-6 col-md-4 col-xl-3">
                <div class="card uhms-stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small mb-1">{{ ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $label)) }}</div>
                        <h3 class="mb-0">{{ number_format($value) }}</h3>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="row g-3">
            @foreach($lists as $key => $items)
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0">{{ ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $key)) }}</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($items->isEmpty())
                            <div class="p-4 text-center text-muted">No records.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <tbody>
                                        @foreach($items as $item)
                                        <tr>
                                            <td>
                                                @if(isset($item->patient))
                                                    <strong>{{ $item->patient->full_name ?? $item->patient->name ?? '—' }}</strong>
                                                @elseif(isset($item->visit) && $item->visit && $item->visit->patient)
                                                    <strong>{{ $item->visit->patient->full_name ?? $item->visit->patient->name ?? '—' }}</strong>
                                                @else
                                                    <strong>#{{ $item->id }}</strong>
                                                @endif
                                                <div class="small text-muted">
                                                    @if(isset($item->department) && $item->department)
                                                        {{ $item->department->name }}
                                                    @endif
                                                    @if(isset($item->status))
                                                        &middot; {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end small text-muted">
                                                {{ optional($item->created_at)->diffForHumans() }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if(empty($stats) && empty($lists))
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="ti ti-layout-dashboard text-muted" style="font-size: 48px;"></i>
                <h5 class="mt-3">Welcome to UHMS</h5>
                <p class="text-muted">Your role-specific dashboard is being prepared. Use the sidebar to access your tasks.</p>
            </div>
        </div>
        @endif
@endsection

@extends('layouts.app')
@section('title', __('queue.queue_board'))

@push('styles')
<style>
    .queue-board .department-card {
        min-height: 200px;
    }
    .queue-number-display {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
    }
    .queue-patient-name {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .priority-emergency { border-left: 4px solid #dc3545 !important; }
    .priority-urgent { border-left: 4px solid #ffc107 !important; }
    .priority-normal { border-left: 4px solid #198754 !important; }
    .serving-card {
        animation: pulse-border 2s infinite;
    }
    @keyframes pulse-border {
        0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(13, 110, 253, 0); }
    }
</style>
@endpush

@section('content')
<div id="queueBoardContent">
<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="ti ti-list-numbers me-2"></i>Queue Board</h4>
        <small class="text-muted">{{ now()->format('l, d F Y — h:i A') }}</small>
    </div>
    <div class="d-flex gap-2">
        @if(request()->boolean('embedded'))
        <button class="btn btn-outline-secondary btn-sm" type="button" data-queue-board-refresh>
            <i class="ti ti-refresh me-1"></i>Refresh
        </button>
        @else
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="location.reload()">
            <i class="ti ti-refresh me-1"></i>Refresh
        </button>
        @endif
        @can('queue.manage')
        <a href="{{ route('admin.queue.manage') }}" class="btn btn-outline-primary btn-sm" @if(request()->boolean('embedded')) target="_blank" rel="noopener" @endif>
            <i class="ti ti-settings me-1"></i>Manage
        </a>
        @endcan
    </div>
</div>

<div class="row queue-board">
    @php
        $triageQueue = $triageQueue ?? collect();
        $triageServing = $triageQueue->where('status', 'serving');
        $triageWaiting = $triageQueue->where('status', 'waiting');
    @endphp
    @if($triageQueue->isNotEmpty())
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card department-card h-100">
            <div class="card-header bg-info text-white d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-white"><i class="ti ti-stethoscope me-1"></i>Triage / Assessment</h6>
                <span class="badge bg-light text-dark">{{ $triageWaiting->count() }} waiting</span>
            </div>
            <div class="card-body p-0">
                @foreach($triageServing as $entry)
                <div class="p-3 bg-info bg-opacity-10 border-bottom serving-card">
                    <div class="text-center">
                        <small class="text-info fw-bold text-uppercase">Now Assessing</small>
                        <div class="queue-number-display text-info">#{{ $entry->queue_number }}</div>
                        <div class="queue-patient-name mt-1">{{ $entry->visit?->patient?->full_name ?? 'Patient' }}</div>
                        @if($entry->priority->value !== 'normal')
                            <x-status-badge :status="$entry->priority" class="mt-1" />
                        @endif
                    </div>
                </div>
                @endforeach

                @foreach($triageWaiting->take(8) as $entry)
                <div class="p-2 px-3 border-bottom d-flex align-items-center justify-content-between priority-{{ $entry->priority->value }}">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold fs-16">#{{ $entry->queue_number }}</span>
                        <span>{{ $entry->visit?->patient?->full_name ?? 'Patient' }}</span>
                    </div>
                    @if($entry->priority->value !== 'normal')
                        <x-status-badge :status="$entry->priority" class="badge-sm" />
                    @endif
                </div>
                @endforeach

                @if($triageWaiting->count() > 8)
                <div class="p-2 text-center text-muted small">
                    +{{ $triageWaiting->count() - 8 }} more waiting
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @foreach($departments as $dept)
        @php
            $deptQueue = $queues->get($dept->id, collect());
            $servingNow = $deptQueue->where('status', 'serving');
            $waitingNow = $deptQueue->where('status', 'waiting');
        @endphp
        @if($deptQueue->isNotEmpty())
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card department-card h-100">
                <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-white">{{ $dept->name }}</h6>
                    <span class="badge bg-light text-dark">{{ $waitingNow->count() }} waiting</span>
                </div>
                <div class="card-body p-0">
                    <!-- Now Serving -->
                    @foreach($servingNow as $entry)
                    <div class="p-3 bg-primary bg-opacity-10 border-bottom serving-card">
                        <div class="text-center">
                            <small class="text-primary fw-bold text-uppercase">Now Serving</small>
                            <div class="queue-number-display text-primary">#{{ $entry->queue_number }}</div>
                            <div class="queue-patient-name mt-1">{{ $entry->visit->patient->full_name }}</div>
                            @if($entry->visit->priority->value !== 'normal')
                                <x-status-badge :status="$entry->visit->priority" class="mt-1" />
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <!-- Waiting List -->
                    @foreach($waitingNow->take(8) as $entry)
                    <div class="p-2 px-3 border-bottom d-flex align-items-center justify-content-between priority-{{ $entry->priority->value }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold fs-16">#{{ $entry->queue_number }}</span>
                            <span>{{ $entry->visit->patient->full_name }}</span>
                        </div>
                        @if($entry->priority->value !== 'normal')
                            <x-status-badge :status="$entry->priority" class="badge-sm" />
                        @endif
                    </div>
                    @endforeach

                    @if($waitingNow->count() > 8)
                    <div class="p-2 text-center text-muted small">
                        +{{ $waitingNow->count() - 8 }} more waiting
                    </div>
                    @endif

                    @if($deptQueue->isEmpty())
                    <div class="p-3 text-center text-muted">No patients in queue</div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    @endforeach

    @if($queues->isEmpty() && $triageQueue->isEmpty())
    <div class="col-12">
        <div class="text-center py-5">
            <i class="ti ti-mood-happy fs-1 text-muted d-block mb-3"></i>
            <h4 class="text-muted">All queues are empty</h4>
            <p class="text-muted">No patients are currently waiting</p>
        </div>
    </div>
    @endif
</div>
</div>
@endsection

@push('scripts')
@unless(request()->boolean('embedded'))
<script>
(function () {
    var refreshMs = 30000;

    if (window.UhmsQueueBoardRefreshTimer) {
        clearInterval(window.UhmsQueueBoardRefreshTimer);
    }

    function refreshQueueBoard() {
        if (document.hidden) {
            return;
        }

        location.reload();
    }

    window.UhmsQueueBoardRefreshTimer = setInterval(refreshQueueBoard, refreshMs);
})();
</script>
@endunless
@endpush

@if($completionReadiness)
@php
    $hasSpecialtyReadiness = isset($specialtyReadiness) && $specialtyReadiness && ! $specialtyReadiness->isFallback;
    $specialtyStatusClass = match($specialtyReadiness->status ?? null) {
        'ready' => 'success',
        'needs_attention' => 'warning text-dark',
        'blocked' => 'danger',
        default => 'secondary',
    };
@endphp
<div class="card mb-3" id="completionReadinessCard">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 small">
            <i class="ti ti-clipboard-check me-1"></i>{{ __('consultation.completion.readiness_title') }}
        </h6>
        <span class="badge bg-{{ $hasSpecialtyReadiness ? $specialtyStatusClass : ($completionReadiness->ready() ? 'success' : 'warning text-dark') }}">
            @if($hasSpecialtyReadiness)
                {{ __('consultation_specialties.readiness.'.$specialtyReadiness->status) }}
            @else
                {{ $completionReadiness->ready() ? __('consultation.completion.ready') : __('consultation.completion.not_ready') }}
            @endif
        </span>
    </div>
    <div class="card-body p-3">
        <div class="list-group list-group-flush">
            @foreach($completionReadiness->requirements() as $requirement)
            <div class="list-group-item px-0 py-2 d-flex align-items-start gap-2">
                <i class="ti {{ $requirement['met'] ? 'ti-circle-check text-success' : 'ti-alert-circle text-warning' }} mt-1"></i>
                <span class="small {{ $requirement['met'] ? 'text-muted' : 'fw-semibold' }}">{{ $requirement['message'] }}</span>
            </div>
            @endforeach
        </div>
        @if($hasSpecialtyReadiness)
            <div class="border-top mt-3 pt-3">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="small fw-semibold">
                        <i class="ti ti-stethoscope me-1"></i>{{ __('consultation_specialties.readiness.title') }}
                    </div>
                    <span class="badge bg-light text-dark border">{{ $specialtyReadiness->score }}%</span>
                </div>

                @foreach([
                    'blockingItems' => ['label' => __('consultation_specialties.readiness.blocking_items'), 'icon' => 'ti-alert-circle text-danger'],
                    'warningItems' => ['label' => __('consultation_specialties.readiness.warning_items'), 'icon' => 'ti-alert-triangle text-warning'],
                    'completedItems' => ['label' => __('consultation_specialties.readiness.completed_items'), 'icon' => 'ti-circle-check text-success'],
                    'optionalItems' => ['label' => __('consultation_specialties.readiness.optional_items'), 'icon' => 'ti-circle-dot text-muted'],
                ] as $groupKey => $group)
                    @php $groupItems = $specialtyReadiness->{$groupKey} ?? []; @endphp
                    @if(! empty($groupItems))
                        <div class="mb-2">
                            <div class="text-muted small fw-semibold mb-1">{{ $group['label'] }}</div>
                            <div class="list-group list-group-flush">
                                @foreach(collect($groupItems)->take(4) as $item)
                                    <div class="list-group-item px-0 py-1 d-flex align-items-start gap-2">
                                        <i class="ti {{ $group['icon'] }} mt-1"></i>
                                        <div class="small">
                                            @if(! empty($item['anchor']))
                                                <a href="{{ $item['anchor'] }}" class="text-decoration-none">{{ $item['label'] }}</a>
                                            @else
                                                <span>{{ $item['label'] }}</span>
                                            @endif
                                            @if(! empty($item['message']) && $groupKey !== 'completedItems')
                                                <div class="text-muted">{{ $item['message'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif

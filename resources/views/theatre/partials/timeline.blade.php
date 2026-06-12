{{-- Procedure timeline. Expects $timeline (array from ProcedureReportService::getTimeline). --}}
<div class="procedure-timeline">
    <style>
        .procedure-timeline { position: relative; padding-left: 2rem; }
        .procedure-timeline::before {
            content: ''; position: absolute; left: 0.65rem; top: 0; bottom: 0;
            width: 2px; background: #e3e6ef;
        }
        .timeline-step { position: relative; margin-bottom: 1.25rem; }
        .timeline-step .marker {
            position: absolute; left: -2rem; top: 0.25rem;
            width: 1.5rem; height: 1.5rem; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.85rem; font-weight: bold;
        }
        .timeline-step.done .marker     { background: #16a34a; }
        .timeline-step.pending .marker  { background: #d97706; }
        .timeline-step.rejected .marker { background: #dc2626; }
        .timeline-step.cancelled .marker{ background: #6b7280; }
        .timeline-step .meta {
            font-size: 0.75rem; color: #6b7280;
        }
        .timeline-step .summary { color: #374151; margin-top: 0.25rem; }
    </style>

    @foreach ($timeline as $step)
        <div class="timeline-step {{ $step['status'] }}" data-stage="{{ $step['stage'] }}">
            <div class="marker">
                @if ($step['status'] === 'done')        ✓
                @elseif ($step['status'] === 'pending') …
                @elseif ($step['status'] === 'rejected')✗
                @elseif ($step['status'] === 'cancelled')⊘
                @endif
            </div>
            <div class="step-body">
                <strong>{{ $step['label'] }}</strong>
                @if ($step['status'] === 'pending')
                    <span class="badge bg-warning text-dark ms-1">{{ __('statuses.default.pending') }}</span>
                @endif
                <div class="meta">
                    @if (!empty($step['timestamp']))
                        {{ \Illuminate\Support\Carbon::parse($step['timestamp'])->format('d M Y · H:i') }}
                    @endif
                    @if (!empty($step['user']))
                        · {{ $step['user'] }}
                    @endif
                </div>
                @if (!empty($step['summary']))
                    <div class="summary">{{ $step['summary'] }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

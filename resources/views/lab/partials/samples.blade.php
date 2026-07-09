{{-- ===================== SPECIMENS / SAMPLES =====================
     Specimen tracking for a lab request. One sample per specimen type; each
     test item is linked to the sample matching its specimen. Only shown for
     specimen-based (lab / pathology) departments. --}}
@php
    $samples = $request->samples ?? collect();
    $unassignedItems = $request->items->whereNull('sample_id')
        ->whereNotIn('status', ['cancelled', 'rejected']);
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-test-pipe me-1"></i>{{ __('samples.panel_title') }}</h6>
        @can('lab.samples.manage')
        @if($request->status !== 'cancelled' && $unassignedItems->isNotEmpty())
        <form method="POST" action="{{ route('admin.lab.samples.generate', $request) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i>{{ $samples->isEmpty() ? __('samples.generate_btn') : __('samples.generate_missing_btn') }}
            </button>
        </form>
        @endif
        @endcan
    </div>
    <div class="card-body">
        @if($samples->isEmpty())
            <div class="text-center text-muted py-3">
                <i class="ti ti-test-pipe-off fs-3 d-block mb-1"></i>
                <span class="small">{{ __('samples.none_yet') }}</span>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('samples.sample_col') }}</th>
                        <th>{{ __('samples.specimen_col') }}</th>
                        <th>{{ __('samples.items_col') }}</th>
                        <th>{{ __('samples.status_col') }}</th>
                        <th>{{ __('samples.chain_col') }}</th>
                        <th class="text-end">{{ __('samples.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($samples as $sample)
                    @php $st = $sample->status_enum; @endphp
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $sample->sample_number }}</span>
                            @if($sample->barcode && $sample->barcode !== $sample->sample_number)
                                <small class="text-muted d-block"><i class="ti ti-barcode me-1"></i>{{ $sample->barcode }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $sample->specimenConfig()['color'] ?? 'secondary' }}-subtle text-{{ $sample->specimenConfig()['color'] ?? 'secondary' }}">
                                <i class="ti {{ $sample->specimenConfig()['icon'] ?? 'ti-flask' }} me-1"></i>{{ $sample->specimenLabel() }}
                            </span>
                            @if($sample->container)<small class="text-muted d-block">{{ $sample->container }}</small>@endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $sample->items->count() }}</span>
                            <small class="text-muted d-block">{{ Str::limit($sample->items->map->display_name->implode(', '), 40) }}</small>
                        </td>
                        <td><span class="badge bg-{{ $st->color() }}"><i class="ti {{ $st->icon() }} me-1"></i>{{ $st->translatedLabel() }}</span></td>
                        <td class="small text-muted">
                            @if($sample->collected_at)
                                <div><i class="ti ti-droplet me-1"></i>{{ $sample->collectedBy->name ?? '—' }} · {{ $sample->collected_at->format('d M H:i') }}</div>
                            @endif
                            @if($sample->received_at)
                                <div><i class="ti ti-check me-1"></i>{{ $sample->receivedBy->name ?? '—' }} · {{ $sample->received_at->format('d M H:i') }}</div>
                            @endif
                            @if($st === \App\Enums\SampleStatus::REJECTED && $sample->rejection_reason)
                                <div class="text-danger"><i class="ti ti-alert-triangle me-1"></i>{{ $sample->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($request->status !== 'cancelled')
                                @if(in_array($st, [\App\Enums\SampleStatus::PENDING, \App\Enums\SampleStatus::REJECTED], true))
                                    @can('lab.samples.collect')
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#collectSampleModal-{{ $sample->id }}" title="{{ __('samples.collect_btn') }}">
                                        <i class="ti ti-droplet"></i>
                                    </button>
                                    @endcan
                                @endif
                                @if($st === \App\Enums\SampleStatus::COLLECTED)
                                    @can('lab.samples.receive')
                                    <x-confirm-form :action="route('admin.lab.samples.receive', $sample)" method="PATCH"
                                        button-class="btn btn-sm btn-outline-success" icon="ti-check" :button-label="''"
                                        :confirm-title="__('samples.receive_confirm_title')" :confirm-text="__('samples.receive_confirm_text', ['number' => $sample->sample_number])"
                                        :confirm-button="__('samples.receive_btn')" />
                                    @endcan
                                @endif
                                @if(! $st->isTerminal())
                                    @can('lab.samples.manage')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectSampleModal-{{ $sample->id }}" title="{{ __('samples.reject_btn') }}">
                                        <i class="ti ti-x"></i>
                                    </button>
                                    @endcan
                                @endif
                                @if($st === \App\Enums\SampleStatus::RECEIVED)
                                    @can('lab.samples.manage')
                                    <x-confirm-form :action="route('admin.lab.samples.dispose', $sample)" method="PATCH"
                                        button-class="btn btn-sm btn-outline-secondary" icon="ti-trash" :button-label="''"
                                        :confirm-title="__('samples.dispose_confirm_title')" :confirm-text="__('samples.dispose_confirm_text', ['number' => $sample->sample_number])"
                                        :confirm-button="__('samples.dispose_btn')" />
                                    @endcan
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($unassignedItems->isNotEmpty())
            <div class="alert alert-warning py-2 mt-3 mb-0 small">
                <i class="ti ti-alert-circle me-1"></i>{{ __('samples.unassigned_note', ['count' => $unassignedItems->count()]) }}
            </div>
        @endif
        @endif
    </div>
</div>

{{-- Collect / Reject modals --}}
@foreach($samples as $sample)
    @php $st = $sample->status_enum; @endphp
    @if($request->status !== 'cancelled')
        @if(in_array($st, [\App\Enums\SampleStatus::PENDING, \App\Enums\SampleStatus::REJECTED], true))
        @can('lab.samples.collect')
        <div class="modal fade" id="collectSampleModal-{{ $sample->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.lab.samples.collect', $sample) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-droplet me-1"></i>{{ __('samples.collect_title') }}: {{ $sample->specimenLabel() }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('samples.barcode_label') }}</label>
                            <input type="text" name="barcode" class="form-control" value="{{ $sample->barcode }}">
                            <div class="form-text">{{ __('samples.barcode_hint') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('samples.container_label') }}</label>
                            <input type="text" name="container" class="form-control" value="{{ $sample->container }}" placeholder="{{ $sample->specimenConfig()['container'] ?? '' }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('samples.notes_label') }}</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $sample->notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('samples.cancel_btn') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('samples.mark_collected_btn') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
        @endif
        @if(! $st->isTerminal())
        @can('lab.samples.manage')
        <div class="modal fade" id="rejectSampleModal-{{ $sample->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.lab.samples.reject', $sample) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="ti ti-x me-1"></i>{{ __('samples.reject_title') }}: {{ $sample->sample_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">{{ __('samples.rejection_reason_label') }} <span class="text-danger">*</span></label>
                            <select name="rejection_reason" class="form-select" required>
                                <option value="">{{ __('samples.select_reason') }}</option>
                                @foreach(__('samples.rejection_reasons') as $reason)
                                    <option value="{{ $reason }}">{{ $reason }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text">{{ __('samples.reject_hint') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('samples.cancel_btn') }}</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-x me-1"></i>{{ __('samples.reject_btn') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
        @endif
    @endif
@endforeach

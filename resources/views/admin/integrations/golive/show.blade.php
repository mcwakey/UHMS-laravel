@extends('layouts.app')

@section('title', __('integrations.go_live_checklist'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="$provider->name" :description="__('integrations.go_live_checklist')" icon="ti-rocket"
            :breadcrumbs="[
                ['label' => __('integrations.go_live_checklist'), 'url' => route('admin.integrations.golive.index')],
                ['label' => $provider->name],
            ]">
            <x-slot:actions>
                @if($checklist->live_ready)
                    <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('integrations.go_live_ready') }}</span>
                @else
                    <span class="badge bg-warning text-dark">{{ __('integrations.not_live_ready') }}</span>
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('integrations.go_live_item') }}</th>
                                <th>{{ __('integrations.status') }}</th>
                                <th style="min-width:340px;">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($checklist->items as $item)
                                <tr>
                                    <td>{{ __('integrations.golive_items.' . $item->item_key) }}
                                        @if($item->evidence_reference)<div class="text-muted small">{{ $item->evidence_reference }}</div>@endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ in_array($item->status, ['passed','waived','not_applicable']) ? 'success' : ($item->status === 'failed' ? 'danger' : 'secondary') }}">{{ $item->status }}</span>
                                    </td>
                                    <td>
                                        @can('integrations.payments.golive.manage')
                                            <form method="POST" action="{{ route('admin.integrations.golive.items.update', $checklist) }}" class="d-flex gap-1 flex-wrap align-items-center">
                                                @csrf
                                                <input type="hidden" name="item_key" value="{{ $item->item_key }}">
                                                <select name="status" class="form-select form-select-sm" style="width:auto;">
                                                    @foreach($itemStatuses as $st)
                                                        <option value="{{ $st }}" @selected($item->status === $st)>{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="evidence_reference" class="form-control form-control-sm" style="width:130px;" placeholder="{{ __('integrations.evidence_reference') }}" value="{{ $item->evidence_reference }}">
                                                <input type="text" name="waiver_reason" class="form-control form-control-sm" style="width:140px;" placeholder="{{ __('integrations.waiver_reason') }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-device-floppy"></i></button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @can('integrations.payments.golive.approve')
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                    <form method="POST" action="{{ route('admin.integrations.golive.signoff', $checklist) }}">
                        @csrf
                        <input type="hidden" name="type" value="finance">
                        <button class="btn btn-outline-secondary btn-sm" @disabled($checklist->finance_signoff_at)>
                            <i class="ti ti-writing-sign me-1"></i>{{ __('integrations.finance_signoff') }}
                            @if($checklist->finance_signoff_at)<i class="ti ti-check text-success ms-1"></i>@endif
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.integrations.golive.signoff', $checklist) }}">
                        @csrf
                        <input type="hidden" name="type" value="it">
                        <button class="btn btn-outline-secondary btn-sm" @disabled($checklist->it_signoff_at)>
                            <i class="ti ti-writing-sign me-1"></i>{{ __('integrations.it_signoff') }}
                            @if($checklist->it_signoff_at)<i class="ti ti-check text-success ms-1"></i>@endif
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.integrations.golive.approve', $checklist) }}" class="ms-auto">
                        @csrf
                        <button class="btn btn-success btn-sm"><i class="ti ti-rocket me-1"></i>{{ __('integrations.go_live_ready') }}</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</div>
@endsection

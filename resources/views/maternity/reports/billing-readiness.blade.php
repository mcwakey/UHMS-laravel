@extends('layouts.app')
@section('title', __('maternity.billing_mapping_readiness'))
@section('content')
<x-page-header :title="__('maternity.billing_mapping_readiness')" icon="ti-receipt">
    <x-slot:actions><a href="{{ route('admin.maternity.reports.index') }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>

<div class="alert alert-info">{{ __('maternity.billing_readiness_no_posting') }}</div>

<form method="POST" action="{{ route('admin.maternity.billing-readiness.update') }}" class="card">
    @csrf
    @method('PATCH')
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.mapping_key') }}</th><th>{{ __('maternity.service') }}</th><th>{{ __('maternity.active') }}</th><th>{{ __('maternity.description') }}</th><th>{{ __('maternity.status') }}</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                    @php($mapping = $row['mapping'])
                    <tr>
                        <td><strong>{{ $row['label'] }}</strong><div class="small text-muted">{{ $row['key'] }}</div></td>
                        <td>
                            <select name="mappings[{{ $row['key'] }}][service_id]" class="form-select">
                                <option value="">{{ __('maternity.mapping_missing') }}</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" @selected((int) $mapping?->service_id === $service->id)>{{ $service->code }} - {{ $service->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="hidden" name="mappings[{{ $row['key'] }}][is_active]" value="0"><input type="checkbox" name="mappings[{{ $row['key'] }}][is_active]" value="1" @checked($mapping?->is_active ?? true)></td>
                        <td><input name="mappings[{{ $row['key'] }}][description]" class="form-control" value="{{ old('mappings.'.$row['key'].'.description', $mapping?->description) }}"></td>
                        <td>
                            @if($row['configured'])
                            <span class="badge bg-success">{{ __('maternity.mapping_configured') }}</span>
                            @endif
                            @foreach($row['warnings'] as $warning)<span class="badge bg-warning text-dark me-1">{{ $warning }}</span>@endforeach
                            @if($mapping?->configured_at)<div class="small text-muted">{{ $mapping->configured_at?->format('d M Y H:i') }}</div>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @can('maternity.billing_readiness.manage')
    <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.save') }}</button></div>
    @endcan
</form>
@endsection

@extends('layouts.app')
@section('title', __('accounting.account_mappings'))

@section('content')
<x-page-header :title="__('accounting.account_mappings')" :description="__('accounting.account_mappings_description')" icon="ti-arrows-random">
    <x-slot:actions>
        @can('accounting.mappings.manage')
            <a class="btn btn-primary" href="{{ route('admin.accounting.mappings.create') }}"><i class="ti ti-plus me-1"></i>{{ __('accounting.new_mapping') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('accounting.mapping_scope') }}</label>
                <select class="form-select" name="scope">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($scopes as $scope)<option value="{{ $scope }}" @selected(request('scope') === $scope)>{{ $scope }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select class="form-select" name="active">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1" @selected(request('active') === '1')>{{ __('common.active') }}</option>
                    <option value="0" @selected(request('active') === '0')>{{ __('common.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.mappings.index') }}"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>{{ __('accounting.mapping_scope') }}</th><th>{{ __('accounting.mapping_key') }}</th><th>{{ __('accounting.mapping_value') }}</th><th>{{ __('accounting.account') }}</th><th>{{ __('accounting.effective_dates') }}</th><th>{{ __('accounting.priority') }}</th><th>{{ __('common.status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($mappings as $mapping)
                    <tr>
                        <td>{{ $mapping->mapping_scope }}</td>
                        <td>{{ $mapping->mapping_key }}</td>
                        <td>{{ $mapping->mapping_value }}</td>
                        <td>{{ $mapping->account?->display_name }}</td>
                        <td>{{ $mapping->effective_from?->format('d M Y') }} - {{ $mapping->effective_to?->format('d M Y') ?? __('accounting.open_ended') }}</td>
                        <td>{{ $mapping->priority }}</td>
                        <td><span class="badge bg-{{ $mapping->is_active ? 'success' : 'secondary' }}">{{ $mapping->is_active ? __('common.active') : __('common.inactive') }}</span></td>
                        <td class="text-end">
                            @can('accounting.mappings.manage')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.mappings.edit', $mapping) }}"><i class="ti ti-edit"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('accounting.no_account_mappings') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end">{{ $mappings->links() }}</div>
@endsection

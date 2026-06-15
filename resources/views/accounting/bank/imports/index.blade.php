@extends('layouts.app')
@section('title', __('accounting.statement_imports'))

@section('content')
<x-page-header :title="__('accounting.statement_imports')" icon="ti-file-import">
    @can('accounting.bank_statements.import')
        <a href="{{ route('admin.accounting.bank.imports.create') }}" class="btn btn-primary"><i class="ti ti-upload me-1"></i>{{ __('accounting.import_statement') }}</a>
    @endcan
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('accounting.bank_account') }}</th>
                        <th>{{ __('common.file') ?? 'File' }}</th>
                        <th>{{ __('accounting.statement_period') }}</th>
                        <th class="text-end">{{ __('accounting.statement_lines') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $import)
                    <tr>
                        <td>{{ $import->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $import->bankAccount?->name }}</td>
                        <td>{{ $import->original_filename ?? '—' }}</td>
                        <td>{{ optional($import->period_start)->format('d M Y') }} – {{ optional($import->period_end)->format('d M Y') }}</td>
                        <td class="text-end">{{ $import->line_count }}</td>
                        <td><span class="badge bg-{{ ['imported'=>'success','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$import->status] ?? 'warning' }}">{{ __('statuses.default.' . $import->status) }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.accounting.bank.imports.show', $import) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('common.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $imports->links() }}
    </div>
</div>
@endsection

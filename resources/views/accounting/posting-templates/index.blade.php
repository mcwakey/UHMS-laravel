@extends('layouts.app')
@section('title', __('accounting.posting_templates'))

@section('content')
<x-page-header :title="__('accounting.posting_templates')" :description="__('accounting.posting_templates_description')" icon="ti-template">
    <x-slot:actions>
        @can('accounting.posting_templates.manage')
            <a class="btn btn-primary" href="{{ route('admin.accounting.posting-templates.create') }}"><i class="ti ti-plus me-1"></i>{{ __('accounting.new_template') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>{{ __('accounting.code') }}</th><th>{{ __('accounting.name') }}</th><th>{{ __('accounting.entry_type') }}</th><th>{{ __('accounting.effective_dates') }}</th><th>{{ __('accounting.journal_lines') }}</th><th>{{ __('common.status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($templates as $template)
                <tr>
                    <td><code>{{ $template->code }}</code></td>
                    <td>{{ $template->name }}</td>
                    <td>{{ ucfirst($template->entry_type) }}</td>
                    <td>{{ $template->effective_from->format('d M Y') }} - {{ $template->effective_to?->format('d M Y') ?? __('accounting.open_ended') }}</td>
                    <td>{{ $template->lines_count }}</td>
                    <td><span class="badge bg-{{ $template->status === 'active' ? 'success' : ($template->status === 'draft' ? 'warning' : 'secondary') }}">{{ ucfirst($template->status) }}</span></td>
                    <td class="text-end">
                        @can('accounting.posting_templates.manage')
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.posting-templates.edit', $template) }}"><i class="ti ti-edit"></i></a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('accounting.no_posting_templates') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end">{{ $templates->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', $template->exists ? __('accounting.edit_template') : __('accounting.new_template'))

@section('content')
<x-page-header :title="$template->exists ? __('accounting.edit_template') : __('accounting.new_template')" :description="__('accounting.posting_template_form_help')" icon="ti-template" />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php
    $defaultLines = $template->entry_type === 'expense'
        ? [
            ['side' => 'debit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_expense_category', 'mapping_key_source' => 'literal:category_id', 'mapping_value_source' => 'category_id'],
            ['side' => 'credit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_payment_method', 'mapping_key_source' => 'literal:method', 'mapping_value_source' => 'payment_method'],
        ]
        : [
            ['side' => 'debit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_payment_method', 'mapping_key_source' => 'literal:method', 'mapping_value_source' => 'payment_method'],
            ['side' => 'credit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_income_category', 'mapping_key_source' => 'literal:category_id', 'mapping_value_source' => 'category_id'],
        ];
    $lines = old('lines', $template->exists ? $template->lines->toArray() : $defaultLines);
@endphp
<form method="POST" action="{{ $template->exists ? route('admin.accounting.posting-templates.update', $template) : route('admin.accounting.posting-templates.store') }}">
    @csrf
    @if($template->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">{{ __('accounting.code') }}</label><input class="form-control" name="code" value="{{ old('code', $template->code) }}" required></div>
        <div class="col-md-5"><label class="form-label">{{ __('accounting.name') }}</label><input class="form-control" name="name" value="{{ old('name', $template->name) }}" required></div>
        <div class="col-md-2"><label class="form-label">{{ __('accounting.entry_type') }}</label><select class="form-select" name="entry_type"><option value="income" @selected(old('entry_type', $template->entry_type) === 'income')>Income</option><option value="expense" @selected(old('entry_type', $template->entry_type) === 'expense')>Expense</option></select></div>
        <div class="col-md-2"><label class="form-label">{{ __('common.status') }}</label><input class="form-control" value="{{ ucfirst($template->status ?: 'draft') }}" disabled></div>
        <div class="col-md-3"><label class="form-label">{{ __('accounting.effective_from') }}</label><input type="date" class="form-control" name="effective_from" value="{{ old('effective_from', $template->effective_from?->toDateString() ?? now()->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('accounting.effective_to') }}</label><input type="date" class="form-control" name="effective_to" value="{{ old('effective_to', $template->effective_to?->toDateString()) }}"></div>
        <div class="col-md-6"><label class="form-label">{{ __('accounting.description') }}</label><input class="form-control" name="description" value="{{ old('description', $template->description) }}"></div>
    </div></div>

    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.template_lines') }}</h5></div><div class="table-responsive">
        <table class="table mb-0"><thead><tr><th>#</th><th>{{ __('accounting.side') }}</th><th>{{ __('accounting.account_source') }}</th><th>{{ __('accounting.mapping_scope') }}</th><th>{{ __('accounting.mapping_key_source') }}</th><th>{{ __('accounting.mapping_value_source') }}</th><th>{{ __('accounting.account') }}</th><th>{{ __('accounting.line_description') }}</th></tr></thead>
        <tbody>
        @foreach($lines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><select class="form-select" name="lines[{{ $index }}][side]">@foreach(['debit','credit'] as $side)<option value="{{ $side }}" @selected(($line['side'] ?? '') === $side)>{{ ucfirst($side) }}</option>@endforeach</select></td>
                <td><select class="form-select" name="lines[{{ $index }}][account_source_type]">@foreach(['mapping','fixed'] as $source)<option value="{{ $source }}" @selected(($line['account_source_type'] ?? '') === $source)>{{ ucfirst($source) }}</option>@endforeach</select></td>
                <td><select class="form-select" name="lines[{{ $index }}][mapping_scope]"><option value="">-</option>@foreach($scopes as $scope)<option value="{{ $scope }}" @selected(($line['mapping_scope'] ?? '') === $scope)>{{ $scope }}</option>@endforeach</select></td>
                <td><input class="form-control" name="lines[{{ $index }}][mapping_key_source]" value="{{ $line['mapping_key_source'] ?? '' }}" placeholder="literal:method"></td>
                <td><input class="form-control" name="lines[{{ $index }}][mapping_value_source]" value="{{ $line['mapping_value_source'] ?? '' }}" placeholder="payment_method"></td>
                <td><select class="form-select" name="lines[{{ $index }}][fixed_account_id]"><option value="">-</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(($line['fixed_account_id'] ?? '') == $account->id)>{{ $account->display_name }}</option>@endforeach</select></td>
                <td><input class="form-control" name="lines[{{ $index }}][description_template]" value="{{ $line['description_template'] ?? '{entry_number}: {description}' }}"></td>
            </tr>
        @endforeach
        </tbody></table>
    </div></div>
    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">{{ __('common.save') }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.posting-templates.index') }}">{{ __('common.cancel') }}</a></div>
</form>

@if($template->exists)
<div class="d-flex gap-2 mt-3">
    @can('accounting.posting_templates.approve')
    <form method="POST" action="{{ route('admin.accounting.posting-templates.approve', $template) }}">@csrf @method('PATCH')<button class="btn btn-success">{{ __('accounting.approve_template') }}</button></form>
    @endcan
    @can('accounting.posting_templates.manage')
    <form method="POST" action="{{ route('admin.accounting.posting-templates.disable', $template) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger">{{ __('accounting.disable_template') }}</button></form>
    @endcan
</div>
@endif
@endsection

@extends('layouts.app')

@section('title', __('sms.sms_templates'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.sms_templates')" :description="__('sms.sms_gateway')" icon="ti-template" />

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('sms.add_template') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.integrations.sms.templates.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">{{ __('sms.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ __('sms.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ __('sms.language') }}</label>
                                <select name="language" class="form-select">
                                    <option value="en" @selected(old('language')==='en')>EN</option>
                                    <option value="fr" @selected(old('language')==='fr')>FR</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ __('sms.body') }} <span class="text-danger">*</span></label>
                                <textarea name="body" rows="3" maxlength="1000" class="form-control" required>{{ old('body') }}</textarea>
                                <div class="form-text text-warning"><i class="ti ti-shield-lock me-1"></i>{{ __('sms.phi_warning') }}</div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="tplActive" checked>
                                <label class="form-check-label" for="tplActive">{{ __('sms.active') }}</label>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('sms.add_template') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($templates->isEmpty())
                            <x-empty-state icon="ti-template" :message="__('sms.no_templates')" />
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('sms.code') }}</th>
                                            <th>{{ __('sms.name') }}</th>
                                            <th>{{ __('sms.language') }}</th>
                                            <th>{{ __('sms.active') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($templates as $template)
                                            <tr>
                                                <td><code>{{ $template->code }}</code></td>
                                                <td>{{ $template->name }}</td>
                                                <td>{{ strtoupper($template->language) }}</td>
                                                <td>
                                                    @if($template->is_active)
                                                        <span class="badge bg-success">{{ __('sms.active') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">{{ $templates->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', __('procedures.configure_procedure_title', ['name' => $service->name]))

@php
    $templateLabels = [
        'PRE_OP' => __('procedures.tpl_pre_op'),
        'ANAESTHESIA' => __('procedures.tpl_anaesthesia'),
        'OPERATIVE_NOTE' => __('procedures.tpl_operative_note'),
        'POST_OP' => __('procedures.tpl_post_op'),
        'FULL_REPORT' => __('procedures.tpl_full_report'),
    ];
@endphp

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-clipboard-list me-2"></i>{{ $service->name }}</h4>
        <small class="text-muted">
            <code>{{ $service->code }}</code> · {{ optional($service->department)->name }} ·
            {{ __('procedures.price_label') }} {{ number_format((float) $service->price, 2) }}
        </small>
    </div>
    <div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.procedure-catalogue.index') }}">
            <i class="ti ti-arrow-left me-1"></i>{{ __('theatre.back') }}
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-templates">{{ __('procedures.templates') }}</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-consumables">{{ __('procedures.default_consumables') }}</a></li>
</ul>

<div class="tab-content">
    {{-- ─────────── Templates tab ─────────── --}}
    <div class="tab-pane fade show active" id="tab-templates">
        <ul class="nav nav-pills mb-3" role="tablist">
            @foreach($templateTypes as $i => $tt)
                <li class="nav-item">
                    <a class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="pill" href="#tpl-{{ $tt }}">
                        {{ $templateLabels[$tt] ?? $tt }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach($templateTypes as $i => $tt)
                <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="tpl-{{ $tt }}">
                    <div class="row g-3">
                        {{-- Sections column --}}
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>{{ __('procedures.sections') }}</strong>
                                    <button aria-label="{{ __('common.add') }}" title="{{ __('common.add') }}" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#new-section-{{ $tt }}">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="new-section-{{ $tt }}">
                                    <div class="card-body border-bottom">
                                        <form method="POST" action="{{ route('admin.procedure-catalogue.sections.store', $service) }}">
                                            @csrf
                                            <input type="hidden" name="template_type" value="{{ $tt }}">
                                            <div class="mb-2"><input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('procedures.section_name') }}" required></div>
                                            <div class="mb-2"><input type="text" name="description" class="form-control form-control-sm" placeholder="{{ __('procedures.description_optional') }}"></div>
                                            <div class="mb-2"><input type="number" name="sort_order" class="form-control form-control-sm" placeholder="{{ __('procedures.sort_order') }}" value="0"></div>
                                            <button class="btn btn-sm btn-primary" type="submit">{{ __('procedures.add_section') }}</button>
                                        </form>
                                    </div>
                                </div>
                                <ul class="list-group list-group-flush">
                                    @forelse($sectionsByType[$tt] as $section)
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">{{ $section->name }}</div>
                                                @if($section->description)<small class="text-muted">{{ $section->description }}</small>@endif
                                                <div><small class="text-muted">{{ __('procedures.order_prefix') }}: {{ $section->sort_order }}</small></div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.procedure-catalogue.sections.destroy', $section) }}"
                                                  onsubmit="return confirm('{{ __('procedures.delete_section_confirm') }}')">
                                                @csrf @method('DELETE')
                                                <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-center text-muted small py-3">{{ __('procedures.no_sections_yet') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        {{-- Fields column --}}
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>{{ __('procedures.fields') }}</strong>
                                    <button aria-label="{{ __('common.add') }}" title="{{ __('common.add') }}" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#new-field-{{ $tt }}">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="new-field-{{ $tt }}">
                                    <div class="card-body border-bottom">
                                        <form method="POST" action="{{ route('admin.procedure-catalogue.fields.store', $service) }}">
                                            @csrf
                                            <input type="hidden" name="template_type" value="{{ $tt }}">
                                            <div class="row g-2">
                                                <div class="col-md-6"><input type="text" name="label" class="form-control form-control-sm" placeholder="{{ __('procedures.field_label_placeholder') }}" required></div>
                                                <div class="col-md-6"><input type="text" name="field_key" class="form-control form-control-sm" placeholder="{{ __('procedures.field_key_placeholder') }}"></div>
                                                <div class="col-md-6">
                                                    <select name="section_id" class="form-select form-select-sm">
                                                        <option value="">{{ __('procedures.no_section_option') }}</option>
                                                        @foreach($sectionsByType[$tt] as $section)
                                                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <select name="input_type" class="form-select form-select-sm" required>
                                                        @foreach($inputTypes as $it)
                                                            <option value="{{ $it }}">{{ $it }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-12"><input type="text" name="default_value" class="form-control form-control-sm" placeholder="{{ __('procedures.default_value_optional') }}"></div>
                                                <div class="col-md-6"><input type="number" name="sort_order" class="form-control form-control-sm" placeholder="{{ __('procedures.sort_order') }}" value="0"></div>
                                                <div class="col-md-6 d-flex align-items-center">
                                                    <div class="form-check"><input type="checkbox" name="is_required" value="1" class="form-check-input"><label class="form-check-label ms-1">{{ __('common.required') }}</label></div>
                                                </div>
                                                <div class="col-12"><button class="btn btn-sm btn-primary" type="submit">{{ __('procedures.add_field') }}</button></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('procedures.col_label') }}</th><th>{{ __('procedures.col_section') }}</th><th>{{ __('procedures.col_type') }}</th><th>{{ __('procedures.col_req') }}</th><th>{{ __('procedures.col_order') }}</th><th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($fieldsByType[$tt] as $field)
                                            <tr>
                                                <td>
                                                    <div>{{ $field->label }}</div>
                                                    @if($field->field_key)<small class="text-muted"><code>{{ $field->field_key }}</code></small>@endif
                                                </td>
                                                <td>{{ optional($field->section)->name ?? '—' }}</td>
                                                <td><span class="badge bg-light text-dark">{{ $field->input_type }}</span></td>
                                                <td>{!! $field->is_required ? '<i class="ti ti-check text-success"></i>' : '' !!}</td>
                                                <td>{{ $field->sort_order }}</td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('admin.procedure-catalogue.fields.destroy', $field) }}"
                                                          onsubmit="return confirm('{{ __('procedures.delete_field_confirm') }}')">
                                                        @csrf @method('DELETE')
                                                        <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6"><x-empty-state :message="__('procedures.no_fields_yet')" /></td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─────────── Consumables tab ─────────── --}}
    <div class="tab-pane fade" id="tab-consumables">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header py-2"><strong>{{ __('procedures.add_default_consumable') }}</strong></div>
                    <div class="card-body">
                        @if($availableProducts->isEmpty())
                            <div class="alert alert-info py-2 small mb-0">
                                {{ __('procedures.no_products_linked_prefix') }}
                                <a href="{{ route('admin.products.index') }}">{{ __('procedures.products_catalogue') }}</a> {{ __('procedures.link_first_suffix') }}
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.procedure-catalogue.consumables.store', $service) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small">{{ __('procedures.product') }}</label>
                                    <select name="product_id" class="form-select form-select-sm" required>
                                        <option value="">{{ __('procedures.select_product') }}</option>
                                        @foreach($availableProducts as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} @if($p->code)({{ $p->code }})@endif @if($p->unit)— {{ $p->unit }}@endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">{{ __('procedures.default_quantity') }}</label>
                                    <input type="number" step="0.0001" min="0.0001" name="default_quantity" class="form-control form-control-sm" required value="1">
                                </div>
                                <div class="mb-2 form-check">
                                    <input type="checkbox" name="is_required" value="1" class="form-check-input" id="cons-req">
                                    <label class="form-check-label small" for="cons-req">{{ __('procedures.mandatory') }}</label>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">{{ __('common.notes') }}</label>
                                    <textarea name="notes" rows="2" class="form-control form-control-sm"></textarea>
                                </div>
                                <button class="btn btn-sm btn-primary" type="submit">{{ __('procedures.save_consumable') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header py-2"><strong>{{ __('procedures.configured_consumables') }}</strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr><th>{{ __('procedures.product') }}</th><th>{{ __('procedures.unit') }}</th><th class="text-end">{{ __('procedures.default_qty') }}</th><th>{{ __('procedures.req_q') }}</th><th></th></tr>
                            </thead>
                            <tbody>
                            @forelse($serviceConsumables as $sc)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $sc->product->name }}</div>
                                        @if($sc->product->code)<small class="text-muted"><code>{{ $sc->product->code }}</code></small>@endif
                                        @if($sc->notes)<div><small class="text-muted">{{ $sc->notes }}</small></div>@endif
                                    </td>
                                    <td>{{ $sc->product->unit ?? '—' }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $sc->default_quantity, 4, '.', ''), '0'), '.') }}</td>
                                    <td>{!! $sc->is_required ? '<span class="badge bg-warning-subtle text-warning">'.e(__('common.required')).'</span>' : '' !!}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.procedure-catalogue.consumables.destroy', [$service, $sc->product_id]) }}"
                                              onsubmit="return confirm('{{ __('procedures.remove_consumable_confirm') }}')">
                                            @csrf @method('DELETE')
                                            <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty-state :message="__('procedures.no_consumables_configured')" /></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

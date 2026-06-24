@props([
    'departments' => collect(),
    'doctors' => collect(),
    'title' => null,
    'departmentLabel' => null,
    'doctorLabel' => null,
    'availableServicesLabel' => null,
    'selectedServicesLabel' => null,
    'departmentName' => null,
    'doctorName' => null,
    'departmentRequired' => false,
    'doctorRequired' => false,
    'doctorDisabledUntilDepartment' => true,
    'selectedDepartmentId' => null,
    'selectedDoctorId' => null,
    'showExtraServicesToggle' => true,
    'selectedServicesVisible' => false,
    'billingTableVariant' => 'visit',
    'departmentSelectId' => 'departmentSelect',
    'doctorSelectId' => 'doctorSelect',
    'showExtraServicesId' => 'showExtraServices',
    'servicesListId' => 'servicesList',
    'servicesPlaceholderId' => 'servicesPlaceholder',
    'servicesContentId' => 'servicesContent',
    'serviceFilterId' => 'serviceFilter',
    'servicesItemsId' => 'servicesItems',
    'selectedServicesCardId' => 'selectedServicesCard',
    'routeDoctorSummaryId' => 'routeDoctorSummary',
    'billingTableId' => 'billingTable',
    'billingBodyId' => 'billingBody',
    'totalAmountId' => 'totalAmount',
    'departmentPlaceholder' => null,
    'doctorPlaceholder' => null,
    'servicesPlaceholder' => null,
    'serviceFilterPlaceholder' => null,
    'departmentSearchPlaceholder' => null,
    'doctorSearchPlaceholder' => null,
    'estimatedTotalLabel' => null,
])

@php
    $departments = collect($departments);
    $doctors = collect($doctors);
    $title ??= __('visits.dept_services_heading');
    $departmentLabel ??= __('visits.department_filter_label');
    $doctorLabel ??= __('visits.assign_doctor_label');
    $availableServicesLabel ??= __('visits.available_services_label');
    $selectedServicesLabel ??= __('visits.selected_services_label');
    $departmentPlaceholder ??= __('visits.select_department');
    $doctorPlaceholder ??= __('visits.select_dept_first');
    $servicesPlaceholder ??= __('visits.select_dept_load_services');
    $serviceFilterPlaceholder ??= __('visits.filter_services');
    $departmentSearchPlaceholder ??= __('visits.search_dept_placeholder');
    $doctorSearchPlaceholder ??= __('visits.search_doctor_placeholder');
    $estimatedTotalLabel ??= __('visits.overall_total_label');
    $departmentValue = old($departmentName ?? 'department_id', $selectedDepartmentId);
    $doctorValue = old($doctorName ?? 'doctor_id', $selectedDoctorId);
@endphp

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>{{ $title }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">
                    {{ $departmentLabel }}
                    @if($showExtraServicesToggle)
                        <small class="text-muted">{{ __('visits.dept_filters_services') }}</small>
                    @endif
                    @if($departmentRequired)<span class="text-danger">*</span>@endif
                </label>
                <select id="{{ $departmentSelectId }}"
                        @if($departmentName) name="{{ $departmentName }}" @endif
                        class="form-select @error($departmentName ?? 'department_id') is-invalid @enderror"
                        data-placeholder="{{ $departmentSearchPlaceholder }}"
                        style="width:100%"
                        @if($departmentRequired) required @endif>
                    <option value="">{{ $departmentPlaceholder }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) $departmentValue === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error($departmentName ?? 'department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">
                    {{ $doctorLabel }}
                    @if(! $doctorRequired)<small class="text-muted">{{ __('visits.optional_label') }}</small>@endif
                    @if($doctorRequired)<span class="text-danger">*</span>@endif
                </label>
                <select id="{{ $doctorSelectId }}"
                        @if($doctorName) name="{{ $doctorName }}" @endif
                        class="form-select @error($doctorName ?? 'doctor_id') is-invalid @enderror"
                        data-placeholder="{{ $doctorSearchPlaceholder }}"
                        style="width:100%"
                        @if($doctorDisabledUntilDepartment && ! $departmentValue) disabled @endif
                        @if($doctorRequired) required @endif>
                    <option value="">{{ $doctorPlaceholder }}</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ (string) $doctorValue === (string) $doctor->id ? 'selected' : '' }}>
                            {{ str_starts_with($doctor->full_name ?? $doctor->name ?? '', 'Dr. ') ? ($doctor->full_name ?? $doctor->name) : 'Dr. '.($doctor->full_name ?? $doctor->name ?? $doctor->id) }}
                        </option>
                    @endforeach
                    @if($doctorValue && $doctors->where('id', $doctorValue)->isEmpty())
                        <option value="{{ $doctorValue }}" selected>{{ $doctorValue }}</option>
                    @endif
                </select>
                @error($doctorName ?? 'doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <label class="form-label mb-0">{{ $availableServicesLabel }}</label>
                @if($showExtraServicesToggle)
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $showExtraServicesId }}">
                        <label class="form-check-label small text-muted" for="{{ $showExtraServicesId }}">{{ __('visits.show_other_services') }}</label>
                    </div>
                @endif
            </div>
            <div id="{{ $servicesListId }}" class="border rounded p-3 bg-light">
                <div class="text-muted text-center py-3" id="{{ $servicesPlaceholderId }}">
                    <i class="ti ti-list-search me-1"></i>{{ $servicesPlaceholder }}
                </div>
                <div id="{{ $servicesContentId }}" class="d-none">
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="{{ $serviceFilterId }}" class="form-control" placeholder="{{ $serviceFilterPlaceholder }}">
                    </div>
                    <div id="{{ $servicesItemsId }}" style="max-height: 280px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>

        <div id="{{ $selectedServicesCardId }}" class="{{ $selectedServicesVisible ? '' : 'd-none' }}">
            <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>{{ $selectedServicesLabel }}</label>
            <div id="{{ $routeDoctorSummaryId }}" class="small text-muted mb-2"></div>
            <div class="table-responsive">
                @if($billingTableVariant === 'quantity')
                    <table class="table table-sm table-bordered mb-0" id="{{ $billingTableId }}">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('appointments.service') }}</th>
                                <th class="text-center" style="width: 70px;">{{ __('appointments.qty') }}</th>
                                <th class="text-end" style="width: 100px;">{{ __('appointments.unit_price') }}</th>
                                <th class="text-end" style="width: 100px;">{{ __('common.total') }}</th>
                                <th style="width: 36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="{{ $billingBodyId }}"></tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="text-end">{{ $estimatedTotalLabel }}</td>
                                <td class="text-end" id="{{ $totalAmountId }}">&#8373;0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <table class="table table-sm table-hover table-bordered mb-0" id="{{ $billingTableId }}">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('visits.service_name') }}</th>
                                <th class="text-end" style="width: 120px;">{{ __('visits.price_col') }}</th>
                                <th class="text-center" style="width: 50px;">{{ __('visits.action_col') }}</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $billingBodyId }}"></tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td class="text-end text-primary">{{ $estimatedTotalLabel }}</td>
                                <td class="text-end text-primary" id="{{ $totalAmountId }}">&#8373;0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

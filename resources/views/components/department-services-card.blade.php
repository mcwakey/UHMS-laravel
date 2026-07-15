@props([
    'departments' => collect(),
    'doctors' => collect(),
    'selectedDepartmentId' => null,
    'selectedDoctorId' => null,
    'selectedServicesVisible' => false,
])

@php
    $departments = collect($departments);
    $doctors = collect($doctors);
    $isVisitEdit = request()->routeIs('admin.visits.edit', 'records.visits.edit');

    $title = __('visits.dept_services_heading');
    $departmentLabel = __('visits.department_filter_label');
    $doctorLabel = $isVisitEdit ? __('visits.route_doctor') : __('visits.assign_doctor_label');
    $availableServicesLabel = __('visits.available_services_label');
    $selectedServicesLabel = __('visits.selected_services_label');
    $departmentPlaceholder = __('visits.select_department');
    $doctorPlaceholder = $isVisitEdit ? __('visits.select_dept_load_doctors') : __('visits.select_dept_first');
    $servicesPlaceholder = __('visits.select_dept_load_services');
    $serviceFilterPlaceholder = __('visits.filter_services');
    $departmentSearchPlaceholder = __('visits.search_dept_placeholder');
    $doctorSearchPlaceholder = __('visits.search_doctor_placeholder');
    $estimatedTotalLabel = $isVisitEdit ? __('visits.est_total') : __('visits.overall_total_label');

    $departmentValue = old('department_id', $selectedDepartmentId);
    $doctorValue = old('doctor_id', $selectedDoctorId);
    $showExtraServicesToggle = ! $isVisitEdit;
    $doctorDisabledUntilDepartment = true;
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
                </label>
                <select id="departmentSelect"
                        name="department_id"
                        class="form-select @error('department_id') is-invalid @enderror"
                        data-placeholder="{{ $departmentSearchPlaceholder }}"
                        style="width:100%">
                    <option value="">{{ $departmentPlaceholder }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) $departmentValue === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">
                    {{ $doctorLabel }}
                    <small class="text-muted">{{ __('visits.optional_label') }}</small>
                </label>
                <select id="doctorSelect"
                        name="doctor_id"
                        class="form-select @error('doctor_id') is-invalid @enderror"
                        data-placeholder="{{ $doctorSearchPlaceholder }}"
                        style="width:100%"
                        @if($doctorDisabledUntilDepartment && ! $departmentValue) disabled @endif>
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
                @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <label class="form-label mb-0">{{ $availableServicesLabel }}</label>
                @if($showExtraServicesToggle)
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="showExtraServices">
                        <label class="form-check-label small text-muted" for="showExtraServices">{{ __('visits.show_other_services') }}</label>
                    </div>
                @endif
            </div>
            <div id="servicesList" class="border rounded p-3 bg-light">
                <div class="text-muted text-center py-3" id="servicesPlaceholder">
                    <i class="ti ti-list-search me-1"></i>{{ $servicesPlaceholder }}
                </div>
                <div id="servicesContent" class="d-none">
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="serviceFilter" class="form-control" placeholder="{{ $serviceFilterPlaceholder }}">
                    </div>
                    <div id="servicesItems" style="max-height: 280px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>

        <div id="selectedServicesCard" class="{{ $selectedServicesVisible ? '' : 'd-none' }}">
            <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>{{ $selectedServicesLabel }}</label>
            <div id="routeDoctorSummary" class="small text-muted mb-2"></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered mb-0" id="billingTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('visits.service_name') }}</th>
                            <th class="text-end" style="width: 120px;">{{ __('visits.price_col') }}</th>
                            <th class="text-center" style="width: 50px;">{{ __('visits.action_col') }}</th>
                        </tr>
                    </thead>
                    <tbody id="billingBody"></tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td class="text-end text-primary">{{ $estimatedTotalLabel }}</td>
                            <td class="text-end text-primary" id="totalAmount">&#8373;0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

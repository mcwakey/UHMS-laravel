@extends('layouts.app')
@section('title', __('lab.tests_title'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-flask me-2"></i>{{ __('lab.test_catalog_title') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-md" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="ti ti-folder-plus me-1"></i>{{ __('lab.add_category') }}
        </button>
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addTestModal">
            <i class="ti ti-plus me-1"></i>{{ __('lab.add_test') }}
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Categories Sidebar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-folders me-1"></i>{{ __('lab.categories_heading') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($categories as $cat)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-medium">{{ $cat->name }}</span>
                            <span class="badge bg-soft-primary ms-1">{{ $cat->tests_count }}</span>
                            @if(!$cat->is_active)
                                <span class="badge bg-danger ms-1">{{ __('lab.inactive_badge') }}</span>
                            @endif
                            @if($cat->description)
                                <br><small class="text-muted">{{ Str::limit($cat->description, 50) }}</small>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button aria-label="Actions" title="Actions" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCategoryModal-{{ $cat->id }}">
                                        <i class="ti ti-edit me-1"></i>{{ __('lab.edit_action') }}
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('admin.lab.categories.destroy', $cat) }}" onsubmit="return confirm('{{ __('lab.delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="ti ti-trash me-1"></i>{{ __('lab.delete_action') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal-{{ $cat->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.lab.categories.update', $cat) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('lab.edit_category_title') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('lab.name_label') }} <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('lab.inv_department_label') }}</label>
                                            <select name="department_id" class="form-select">
                                                <option value="">{{ __('lab.unlinked_option') }}</option>
                                                @foreach($investigationDepartments as $dept)
                                                    <option value="{{ $dept->id }}" {{ $cat->department_id == $dept->id ? 'selected' : '' }}>
                                                        {{ $dept->name }} ({{ $dept->result_type?->translatedLabel() ?? '—' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">{{ __('lab.department_help') }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('lab.description_label') }}</label>
                                            <textarea name="description" class="form-control" rows="3">{{ $cat->description }}</textarea>
                                        </div>
                                        <div class="form-check">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="catActive-{{ $cat->id }}" {{ $cat->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="catActive-{{ $cat->id }}">{{ __('lab.active_label') }}</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                                        <button type="submit" class="btn btn-primary">{{ __('lab.update_button') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">
                        {{ __('lab.no_categories') }}
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Tests Table -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>{{ __('lab.lab_tests_heading') }}</h6>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('lab.search_label') }}..." value="{{ request('search') }}" style="width: 150px;">
                        <select name="category_id" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                            <option value="">{{ __('lab.all_categories') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('lab.code_col') }}</th>
                                <th>{{ __('lab.name_label') }}</th>
                                <th>{{ __('lab.category_label') }}</th>
                                <th>{{ __('lab.criteria_col') }}</th>
                                <th>{{ __('lab.price_col_label') }}</th>
                                <th>{{ __('lab.status_col_label') }}</th>
                                <th class="text-end">{{ __('lab.actions_col') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tests as $test)
                            <tr>
                                <td><span class="badge bg-light text-dark fw-medium">{{ $test->code }}</span></td>
                                <td class="fw-medium">{{ $test->name }}</td>
                                <td>{{ $test->category->name ?? '-' }}</td>
                                <td>
                                    @if($test->criteria->isNotEmpty())
                                        @foreach($test->criteria->take(3) as $criterion)
                                            <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                        @endforeach
                                        @if($test->criteria->count() > 3)
                                            <small class="text-muted">+{{ $test->criteria->count() - 3 }} more</small>
                                        @endif
                                    @else
                                        <small>{{ $test->normal_range ?? '-' }} {{ $test->unit ?? '' }}</small>
                                    @endif
                                </td>
                                <td>{{ $test->price ? 'GH₵ ' . number_format($test->price, 2) : '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $test->is_active ? 'success' : 'danger' }}">
                                        {{ $test->is_active ? __('lab.active_status') : __('lab.inactive_status') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button aria-label="Actions" title="Actions" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editTestModal-{{ $test->id }}">
                                                    <i class="ti ti-edit me-1"></i>{{ __('lab.edit_action') }}
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('admin.lab.tests.toggle', $test) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ti ti-{{ $test->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                                        {{ $test->is_active ? __('lab.deactivate_action') : __('lab.activate_action') }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Test Modal -->
                            <div class="modal fade" id="editTestModal-{{ $test->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.lab.tests.update', $test) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('lab.edit_test_title') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('lab.category_label') }} <span class="text-danger">*</span></label>
                                                    <select name="category_id" class="form-select test-category-select" required>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}" data-result-type="{{ $cat->department?->result_type?->value ?? 'parameters' }}" {{ $test->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}{{ $cat->department ? ' — ' . $cat->department->name : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <label class="form-label">{{ __('lab.name_label') }} <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $test->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">{{ __('lab.code_col') }} <span class="text-danger">*</span></label>
                                                        <input type="text" name="code" class="form-control" value="{{ $test->code }}" required>
                                                    </div>
                                                </div>
                                                <div class="mt-3 result-type-block result-type-parameters">
                                                    <label class="form-label fw-medium">{{ __('lab.criteria_label') }} <small class="text-muted">(parameters)</small></label>
                                                    <div class="criteria-container" data-next-index="{{ max(1, $test->criteria->count()) }}">
                                                        @forelse($test->criteria as $criterionIndex => $criterion)
                                                        <div class="criteria-row row g-2 mb-2">
                                                            <div class="col-md-4">
                                                                <input type="text" name="criteria[{{ $criterionIndex }}][name]" class="form-control" value="{{ $criterion->name }}" placeholder="e.g. Haemoglobin">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" name="criteria[{{ $criterionIndex }}][normal_range]" class="form-control" value="{{ $criterion->normal_range }}" placeholder="Range">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input type="text" name="criteria[{{ $criterionIndex }}][unit]" class="form-control" value="{{ $criterion->unit }}" placeholder="Unit">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger w-100 remove-criterion-row"><i class="ti ti-x"></i></button>
                                                            </div>
                                                        </div>
                                                        @empty
                                                        <div class="criteria-row row g-2 mb-2">
                                                            <div class="col-md-4">
                                                                <input type="text" name="criteria[0][name]" class="form-control" value="Result" placeholder="e.g. Result">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" name="criteria[0][normal_range]" class="form-control" value="{{ $test->normal_range }}" placeholder="Range">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input type="text" name="criteria[0][unit]" class="form-control" value="{{ $test->unit }}" placeholder="Unit">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger w-100 remove-criterion-row"><i class="ti ti-x"></i></button>
                                                            </div>
                                                        </div>
                                                        @endforelse
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary add-criterion-row">
                                                        <i class="ti ti-plus me-1"></i>{{ __('lab.add_criterion') }}
                                                    </button>
                                                </div>
                                                <div class="mt-3 result-type-block result-type-richtext d-none">
                                                    <label class="form-label fw-medium">{{ __('lab.desc_template_label') }} <small class="text-muted">(rich text)</small></label>
                                                    <textarea name="description_template" class="form-control" rows="6" placeholder="Default report skeleton: findings, impressions, conclusions...">{{ $test->description_template }}</textarea>
                                                    <div class="form-text">Pre-filled into the result form for richtext-type investigation departments.</div>
                                                </div>
                                                <div class="mt-3">
                                                    <label class="form-label">{{ __('samples.default_specimen_label') }}</label>
                                                    <select name="default_specimen_type" class="form-select">
                                                        <option value="">{{ __('samples.default_specimen_none') }}</option>
                                                        @foreach(config('specimens.types') as $code => $def)
                                                            <option value="{{ $code }}" {{ $test->default_specimen_type === $code ? 'selected' : '' }}>
                                                                {{ \Illuminate\Support\Facades\Lang::has('samples.specimen.'.$code) ? __('samples.specimen.'.$code) : ($def['label'] ?? $code) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text">{{ __('samples.default_specimen_hint') }}</div>
                                                </div>
                                                <div class="mt-3">
                                                    <label class="form-label">{{ __('lab.price_label_ghc') }}</label>
                                                    <input type="number" name="price" class="form-control" value="{{ $test->price }}" step="0.01" min="0">
                                                </div>
                                                <div class="form-check mt-3">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="testActive-{{ $test->id }}" {{ $test->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="testActive-{{ $test->id }}">{{ __('lab.active_label') }}</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('lab.update_button') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ti ti-flask fs-1 d-block mb-2"></i>
                                    {{ __('lab.no_tests_found') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($tests->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $tests->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('lab.add_category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('lab.name_label') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Haematology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('lab.inv_department_label') }}</label>
                        <select name="department_id" class="form-select">
                            <option value="">{{ __('lab.unlinked_option') }}</option>
                            @foreach($investigationDepartments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->result_type?->translatedLabel() ?? '—' }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('lab.department_help') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('lab.description_label') }}</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('lab.create_category_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Test Modal -->
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.tests.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('lab.add_test_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('lab.category_label') }} <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select test-category-select" required>
                            <option value="">{{ __('lab.select_category_opt') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" data-result-type="{{ $cat->department?->result_type?->value ?? 'parameters' }}">{{ $cat->name }}{{ $cat->department ? ' — ' . $cat->department->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('lab.name_label') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Full Blood Count" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('lab.code_col') }} <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. FBC" required>
                        </div>
                    </div>
                    <div class="mt-3 result-type-block result-type-parameters">
                        <label class="form-label fw-medium">{{ __('lab.criteria_label') }} <small class="text-muted">(parameters)</small></label>
                        <div class="criteria-container" data-next-index="1">
                            <div class="criteria-row row g-2 mb-2">
                                <div class="col-md-4">
                                    <input type="text" name="criteria[0][name]" class="form-control" value="Result" placeholder="e.g. Haemoglobin">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="criteria[0][normal_range]" class="form-control" placeholder="Range">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="criteria[0][unit]" class="form-control" placeholder="Unit">
                                </div>
                                <div class="col-md-1">
                                    <button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger w-100 remove-criterion-row"><i class="ti ti-x"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary add-criterion-row">
                            <i class="ti ti-plus me-1"></i>{{ __('lab.add_criterion') }}
                        </button>
                    </div>
                    <div class="mt-3 result-type-block result-type-richtext d-none">
                        <label class="form-label fw-medium">{{ __('lab.desc_template_label') }} <small class="text-muted">(rich text)</small></label>
                        <textarea name="description_template" class="form-control" rows="6" placeholder="Default report skeleton: findings, impressions, conclusions..."></textarea>
                        <div class="form-text">Pre-filled into the result form for richtext-type investigation departments.</div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">{{ __('samples.default_specimen_label') }}</label>
                        <select name="default_specimen_type" class="form-select">
                            <option value="">{{ __('samples.default_specimen_none') }}</option>
                            @foreach(config('specimens.types') as $code => $def)
                                <option value="{{ $code }}">
                                    {{ \Illuminate\Support\Facades\Lang::has('samples.specimen.'.$code) ? __('samples.specimen.'.$code) : ($def['label'] ?? $code) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('samples.default_specimen_hint') }}</div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">{{ __('lab.price_label_ghc') }}</label>
                        <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('lab.create_test_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    var addButton = event.target.closest('.add-criterion-row');
    if (addButton) {
        var container = addButton.previousElementSibling;
        var index = parseInt(container.dataset.nextIndex || container.querySelectorAll('.criteria-row').length || 0, 10);
        container.insertAdjacentHTML('beforeend', criterionRow(index));
        container.dataset.nextIndex = index + 1;
        return;
    }

    var removeButton = event.target.closest('.remove-criterion-row');
    if (removeButton) {
        var row = removeButton.closest('.criteria-row');
        var container = row.closest('.criteria-container');
        if (container.querySelectorAll('.criteria-row').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(function (input) {
                input.value = input.name.indexOf('[name]') !== -1 ? 'Result' : '';
            });
        }
    }
});

function criterionRow(index) {
    return '<div class="criteria-row row g-2 mb-2">'
        + '<div class="col-md-4"><input type="text" name="criteria[' + index + '][name]" class="form-control" placeholder="e.g. Haemoglobin"></div>'
        + '<div class="col-md-4"><input type="text" name="criteria[' + index + '][normal_range]" class="form-control" placeholder="Range"></div>'
        + '<div class="col-md-3"><input type="text" name="criteria[' + index + '][unit]" class="form-control" placeholder="Unit"></div>'
        + '<div class="col-md-1"><button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger w-100 remove-criterion-row"><i class="ti ti-x"></i></button></div>'
        + '</div>';
}

/**
 * Toggle the criteria editor vs description template based on the linked
 * department's result_type for the selected category.
 */
function syncResultTypeBlocks(select) {
    if (!select) return;
    var modal = select.closest('.modal-content') || select.closest('.modal') || document;
    var opt = select.options[select.selectedIndex];
    var rt = (opt && opt.dataset.resultType) || 'parameters';
    var isRichtext = rt === 'richtext';

    modal.querySelectorAll('.result-type-parameters').forEach(function (el) {
        el.classList.toggle('d-none', isRichtext);
        el.querySelectorAll('input, textarea').forEach(function (i) { i.disabled = isRichtext; });
    });
    modal.querySelectorAll('.result-type-richtext').forEach(function (el) {
        el.classList.toggle('d-none', !isRichtext);
        el.querySelectorAll('input, textarea').forEach(function (i) { i.disabled = !isRichtext; });
    });
}

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('test-category-select')) {
        syncResultTypeBlocks(e.target);
    }
});

// Initialise visible test modals on open.
document.addEventListener('shown.bs.modal', function (e) {
    var sel = e.target.querySelector('.test-category-select');
    if (sel) syncResultTypeBlocks(sel);
});
</script>
@endpush

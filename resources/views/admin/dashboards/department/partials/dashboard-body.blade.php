@include('admin.dashboards.department.partials._chrome')

@php
    // Department TYPE chooses the layout family (structure / emphasis); a missing
    // family safely falls back to the generic department layout.
    $layoutFamily = $layout_family ?? 'generic_department';
    $layoutView = 'admin.dashboards.department.partials.layouts.'.$layoutFamily;
@endphp
@includeFirst([$layoutView, 'admin.dashboards.department.partials.layouts.generic_department'])

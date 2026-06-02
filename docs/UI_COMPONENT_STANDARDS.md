# UHMS UI Component Standards

How to use the shared UI components. **Reuse these before hand-writing markup.** Delivered components are Blade anonymous components in `resources/views/components/` (work in all 531 Blade views, no build step). Vue islands reuse the existing `resources/js/Components`.

## Delivered now

### `<x-status-badge>` — workflow status
```blade
<x-status-badge :status="$invoice->status" domain="invoice" />
<x-status-badge :status="$case->final_triage_category" domain="triage" />
<x-status-badge :status="$request->priority" domain="priority" />
<x-status-badge status="LOW" domain="stock" label="Low Stock" size="sm" class="ms-2" />
```
Props: `status` (string|enum), `domain` (key in `config/ui.php`), `label` (override text), `size` (`sm`), `icon` (`ti-*`). Colour comes from `config/ui.php`; text is humanised; contrast handled. **Add new statuses to `config/ui.php`, never inline.**

### `<x-page-header>` — page title block
```blade
<x-page-header title="Patients" description="Manage patient folders and merge history." icon="ti-users"
               :breadcrumbs="[['label'=>'Home','url'=>route('admin.dashboard')],['label'=>'Patients']]">
    <x-slot:actions>
        <a href="{{ route('admin.patients.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New Patient</a>
    </x-slot:actions>
</x-page-header>
```
Props: `title`, `description`, `icon`, `breadcrumbs` (array). Named slot `actions` (top-right).

### `<x-empty-state>` — empty tables/lists
```blade
@forelse($rows as $row) … @empty
    <tr><td colspan="6">
        <x-empty-state icon="ti-ambulance" title="No active emergencies"
                       message="No emergency cases are currently active.">
            @can('emergency.case.create')
            <x-slot:action><a href="…" class="btn btn-sm btn-primary">Create Emergency Case</a></x-slot:action>
            @endcan
        </x-empty-state>
    </td></tr>
@endforelse
```

## Already in the codebase (reuse, don't duplicate)

| Need | Existing |
|---|---|
| Confirm dialog (Vue) | `resources/js/Components/UhmsConfirmDialog.vue` |
| Permission guard (Vue) | `resources/js/Components/Can.vue` |
| Permission guard (Blade) | `@can`, `@canany`, `@cannot` |
| Searchable select | Select2 (global) on `<select class="select2">` |
| Date range | daterangepicker (global) |
| Confirm (Blade async) | SweetAlert2 (`Swal.fire`) |
| Modal shell | `resources/views/components/modal-popup.blade.php` |
| Patient header | `resources/views/partials/patient-visit-header.blade.php`, `partials/patient-card.blade.php` |
| Clinical timeline | `App\Services\VisitPreviewService` + visit-preview partials |
| KPI card pattern | `card h-100 border-start border-{variant} border-3` (reports/statistics) |

## Recommended next extractions (Phase 5 — not yet built)

Document the markup, extract when touched:

- `<x-stat-card label value format color :route />` — from the repeated KPI card.
- `<x-filter-bar>` — the GET-form filter card (search/date/department/status + Apply/Reset).
- `<x-data-table>` — header + `.table-responsive` + pagination + empty-state wrapper.
- `<x-action-menu>` — dropdown for row actions (View/Edit/Print/Delete, danger last).
- `<x-confirm-form>` — POST form wrapped in a SweetAlert2 confirm (destructive actions).
- `<x-print-layout>` — print-only document chrome.

## Component contract (for any new component)

1. `@props` with sensible defaults.
2. Merge extra `class`/attributes via `{{ $attributes->merge([...]) }}`.
3. Pure Bootstrap 5 + Tabler classes (no bespoke CSS that needs a rebuild).
4. Accessible (text + `aria-*`, not colour-only).
5. Covered by a render test (see `tests/Feature/UiComponentsTest.php`).

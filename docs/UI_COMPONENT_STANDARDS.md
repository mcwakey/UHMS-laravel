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

## Phase 5 components (delivered)

### `<x-stat-card>` — KPI / dashboard card
```blade
<x-stat-card title="Today’s Visits" :value="$count" icon="ti-stethoscope" variant="primary" format="number"
             subtitle="OPD + Emergency" :route="route('admin.statistics.activity')" />
```
Props: `title`, `value`, `subtitle`, `icon` (ti-*), `variant` (bootstrap, default `primary`), `route` (makes the whole card a `.stretched-link`), `trend` (free text), `format` (`number|currency|percent`). Uses the canonical `card h-100 border-start border-{variant} border-3`.

### `<x-filter-bar>` — GET filter/search card
```blade
<x-filter-bar :reset-url="route('admin.patients.index')">
    <div class="col-md-4"><label class="form-label">Search</label><input name="search" class="form-control" value="{{ request('search') }}"></div>
</x-filter-bar>
```
Props: `action` (defaults to current URL), `method` (default `GET`), `resetUrl`, `title`, `icon`, `collapsible`, `applyLabel`, `resetLabel`. Place filter fields (`col-*` divs) in the slot — **Apply + Reset are added automatically**; query string is preserved via plain GET.

### `<x-data-table>` — standard table wrapper
```blade
<x-data-table :paginator="$patients">
    <x-slot:head><tr><th>Patient</th><th class="text-end">Actions</th></tr></x-slot:head>
    @forelse($patients as $p) <tr>…</tr>
    @empty <tr><td colspan="2"><x-empty-state message="No patients found." /></td></tr> @endforelse
</x-data-table>
```
Props: `paginator`, `striped`, `hover` (default true), `alignMiddle` (default true), `responsive` (default true), `tableClass`, `pagination` (default true). Wraps `card → .table-responsive → table table-hover align-middle`; renders pagination bottom-right (`withQueryString`). It only **wraps** markup — never loads/hides data; the empty state stays explicit in the slot.

### `<x-action-menu>` — row action dropdown
```blade
<x-action-menu>
    <a href="…" class="dropdown-item"><i class="ti ti-eye me-2"></i>View</a>
    @can('x.edit')<a href="…" class="dropdown-item"><i class="ti ti-edit me-2"></i>Edit</a>@endcan
    <div class="dropdown-divider"></div>
    <x-confirm-form … button-class="dropdown-item text-danger" />
</x-action-menu>
```
Props: `label` (aria-label, default `Actions`), `icon`, `align` (default `end`), `size` (default `sm`). Order View → Edit → Print → Cancel/Delete, **danger last**, behind `@can`. Trigger is accessible (`aria-label`).

### `<x-confirm-form>` — destructive/high-risk confirmed action
```blade
<x-confirm-form :action="route('admin.payments.reverse', $payment)" method="POST"
    button-label="Reverse Payment" button-class="btn btn-danger" icon="ti-rotate"
    confirm-title="Reverse this payment?" confirm-text="This creates a reversal and updates the balance."
    confirm-button="Yes, reverse" require-reason />
```
Props: `action`, `method` (default POST; spoofed for PUT/PATCH/DELETE), `buttonLabel`, `buttonClass`, `icon`, `confirmTitle`, `confirmText`, `confirmButton`, `cancelButton`, `requireReason`, `reasonName`, `reasonPlaceholder`, `disabled`, `disabledReason`. CSRF + method spoofing included; **SweetAlert2 confirm** (native `confirm()`/`prompt()` fallback); collects a reason when `require-reason`; prevents double submit; `disabled` explains why via `title`. **High-risk actions must set `require-reason`.**

### `<x-print-layout>` — print-friendly document shell
```blade
<x-print-layout title="Invoice" :patient="$patient" :visit="$visit" subtitle="INV-001"
                :signatures="['Prepared by','Authorised by']">
    {{-- document body --}}
</x-print-layout>
```
Props: `title`, `patient`, `visit`, `subtitle`, `generatedAt`, `signatures`. Standalone HTML (no sidebar/navbar), hospital header, document title, patient/visit context, generated timestamp, signature blocks, `d-print-none` print/back buttons, black-on-white print CSS. Use for invoice, receipt, consultation summary, visit preview, MAR chart, lab result, theatre report, blood issue/transfusion, claims.

## Component contract (for any new component)

1. `@props` with sensible defaults.
2. Merge extra `class`/attributes via `{{ $attributes->merge([...]) }}`.
3. Pure Bootstrap 5 + Tabler classes (no bespoke CSS that needs a rebuild).
4. Accessible (text + `aria-*`, not colour-only).
5. Covered by a render test (see `tests/Feature/UiComponentsTest.php`).

# UHMS UI Implementation Checklist

Run through this for **every new or edited page/component**. Reviewers should reject UI that fails the **bold** items.

## Structure
- [ ] **Uses `<x-page-header>`** (title + description + primary action top-right)
- [ ] Follows layout order: header → KPIs → filter → content → pagination
- [ ] Breadcrumbs where the page is nested

## Permissions & modules
- [ ] **Actions wrapped in `@can` / `<Can>`; backend still enforces**
- [ ] No buttons that always 403
- [ ] Hidden when its module is disabled (route uses `module:` middleware)

## Data display
- [ ] **Workflow statuses use `<x-status-badge>` (no inline `badge bg-*`)**
- [ ] New statuses added to `config/ui.php` (not hard-coded colours)
- [ ] Tables in `.table-responsive`, actions right-aligned, danger action last
- [ ] **`<x-empty-state>` for empty lists**
- [ ] No hidden critical clinical/financial/stock information

## Forms & feedback
- [ ] Field-level validation (`@error`) shown by the field
- [ ] **Success/error feedback (flash alert or SweetAlert2 toast)**
- [ ] Submit disabled while saving; no double-submit
- [ ] **Destructive actions confirmed (and require a reason where high-risk)**
- [ ] Searchable select for long option lists

## States
- [ ] Loading state for async actions
- [ ] **No raw technical errors shown to users (`SQLSTATE…`)** — friendly message, log the detail
- [ ] Empty/zero values render gracefully (`—`, not blank)

## Visual consistency
- [ ] Bootstrap contextual variants with correct semantic meaning
- [ ] Buttons follow the hierarchy (one primary; outline secondary; danger destructive)
- [ ] Spacing via utilities/tokens, not inline styles
- [ ] Tabler icons consistent with surrounding pages

## Accessibility
- [ ] **Status conveyed by text, not colour alone**
- [ ] Icon-only buttons have `aria-label`/`title`
- [ ] Inputs have labels; focus states visible; adequate contrast

## Responsive
- [ ] Works desktop → tablet → mobile (tables scroll, forms stack, modals fit)

## Print (if printable)
- [ ] Hides chrome, black-on-white, patient/visit context, timestamp, signatures

## Cross-cutting
- [ ] Logs/notifications fired where the action is auditable/notifiable
- [ ] Terminology matches the system glossary (Visit, Invoice, Dispensed, Administered, Rendered, Verified)
- [ ] Reuses existing components instead of duplicating

## Tests
- [ ] Unauthorized action hidden **and** blocked
- [ ] Status badge renders expected label/class (if new domain added to `config/ui.php`)
- [ ] Empty state shows when no data

# UHMS UI Theme Rules — the design law

**These rules are binding for all new and edited UHMS UI.** Machine-readable source of truth: `config/ui.php` (status colours) and `resources/css/uhms-design-system.css` (tokens). When in doubt, copy the **Reports/Statistics** pages — they are the reference implementation.

## 0. Stack (do not change)

Blade + **Bootstrap 5** + **Tabler Icons** (`ti ti-*`). Minor Inertia/Vue islands exist. **No Tailwind, no second CSS framework, no new chart library** (Chart.js + ApexCharts are bundled).

## 1. Brand personality

Clean · modern · medical · professional · calm · fast · readable · trustworthy · data-rich but not chaotic. Never: random, overcrowded, unfinished, too colourful, too dark, hard to scan.

## 2. Colour tokens (semantic — one meaning each)

| Variant | Meaning | Bootstrap class |
|---|---|---|
| primary | brand / admitted / current focus | `bg-primary` / `text-primary` / `btn-primary` |
| success | completed / paid / verified / available / approved | `bg-success` |
| info | active / in-progress / due / current | `bg-info` (+`text-dark`) |
| warning | pending / attention / low / held | `bg-warning text-dark` |
| danger | critical / error / overdue / out / rejected | `bg-danger` |
| secondary | inactive / cancelled / not-stocked / neutral | `bg-secondary` |
| dark | death / black triage / high contrast | `bg-dark` |

**Do not** give one colour two meanings (red ≠ "completed"). Tints (`warning`, `info`, `light`) **must** add `text-dark`.

## 3. Typography

Page title `h4.fw-bold`; section title `h5`/`h6.card-title`; body = default; small = `.fs-12`/`.fs-13`; numbers/KPIs `.h3`. Don't invent font sizes.

## 4. Spacing

Page header bottom gap = `--uhms-content-gap` (1rem); between cards `g-3`; inside forms `g-2`; card padding 1rem; radius `--uhms-radius-md` (8px). Use Bootstrap spacing utilities (`mb-3`, `gap-2`), not inline styles.

## 5. Layout

`PageHeader → [KPI cards] → [FilterBar] → main content → [secondary] → pagination`. Primary action top-right of the header. No randomly placed actions.

## 6. Buttons

One primary per area. Destructive = danger **and** confirmed. Disabled buttons explain why (title/tooltip). Icon-only buttons need `aria-label`. Consistent size per context. Order: View → Edit → Print → Cancel/Delete.

## 7. Forms

Sectioned; labelled; required `*`; per-field `@error`; searchable selects (Select2) for long lists; consistent date pickers; disable submit while saving; success/error feedback; no giant ungrouped forms.

## 8. Tables

`table table-hover align-middle` in `.table-responsive`; status via `<x-status-badge>`; right-aligned actions; pagination bottom-right; `<x-empty-state>` when empty; wrap/scroll wide tables.

## 9. Modals

Title + explanation + body + in-modal validation + cancel/submit + loading; close on success; reset on close. Large clinical data → page/drawer, not modal. Destructive → require reason.

## 10. Cards

Consistent border/shadow (from design CSS), title top, equal heights in a grid (`h-100`), no overcrowding. KPI = `card h-100 border-start border-{variant} border-3`.

## 11. Badges / status — **use `<x-status-badge>`**

Never hand-roll `badge bg-*` for a workflow status. Use `<x-status-badge :status="$x->status" domain="<domain>"/>`. Domains: `visit, invoice, payment, mar, stock, requisition, emergency, triage, disposition, theatre, lab, blood_unit, blood_request, blood_issue, crossmatch, screening, donor_screening, claim, priority, default`. Add new statuses to `config/ui.php`, not inline. Badges always show **text** (accessibility).

## 12. Dashboards

KPI cards + trend chart + priority list + filter; each answers one question; default date range (month/30 days); never overload.

## 13. Clinical documents

Document container, patient header, section headings, chronological, authorship visible, readable font, print-friendly, no hidden clinical data, graceful long text.

## 14. Print

Hide sidebar/navbar/buttons; hospital header; patient/visit context; generated timestamp; black-on-white; signatures where needed. Applies to invoice, receipt, consultation summary, visit preview, MAR chart, lab result, theatre report, blood issue/transfusion, claims.

## 15. Responsive

Tables scroll horizontally on small screens; forms stack; sidebar collapses; cards wrap; modals fit; no fixed widths; actions never overflow.

## 16. Accessibility

Labels on inputs; visible focus (`--uhms-focus-ring`); sufficient contrast; never colour-only; status badges include text; icon-only buttons get `aria-label`; errors linked to fields.

## 17. Feedback

Every action gives feedback: success/error toast or alert, validation, loading, confirmation. Never fail silently. Never show raw technical errors (`SQLSTATE…`) to users — show a friendly message, log the detail.

## 18. Permission-based UI

Hide actions the user can't perform (`@can` / `<Can>`); backend still enforces. Show a locked reason where helpful. Don't render buttons that always 403.

## 19. Module layout

Each module keeps its established internal pattern (patient folder, clinical workspace, ER control-room, ward care, MAR grid, pharmacy workflow, lab request/result, theatre schedule/case, billing, stock matrix, reports dashboard, admin config). Don't mix them.

## 20. Do / Do Not

**Do:** reuse components, use semantic colours, confirm destructive actions, keep clinical data readable, enforce permissions, follow the layout order.
**Do Not:** introduce a new CSS/chart framework, redesign without audit, hide critical clinical/financial/stock info, rely on colour alone, show disabled modules as active, leave stuck modal backdrops, show raw errors, duplicate components that already exist.

## Status colour reference (from `config/ui.php`)

| Domain | Examples |
|---|---|
| visit | REGISTERED=secondary, EMERGENCY=danger, ADMITTED=primary, COMPLETED=success |
| invoice | UNPAID=danger, PARTIALLY_PAID=warning, PAID=success, CANCELLED=secondary |
| mar | DUE=info, OVERDUE=danger, GIVEN=success, HELD=warning, MISSED=danger |
| stock | OK=success, LOW=warning, CRITICAL/OUT=danger, NOT_STOCKED=secondary |
| triage | RED=danger, ORANGE/YELLOW=warning, GREEN=success, BLACK=dark |
| blood_unit | AVAILABLE=success, QUARANTINED=warning, ISSUED=primary, REJECTED=danger |
| crossmatch | COMPATIBLE=success, WITH_CAUTION=warning, INCOMPATIBLE=danger, OVERRIDE=dark |
| claim | SUBMITTED=info, APPROVED/PAID=success, REJECTED=danger |

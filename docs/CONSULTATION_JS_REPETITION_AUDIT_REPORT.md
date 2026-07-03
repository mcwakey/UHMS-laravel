# Consultation JavaScript Repetition Audit Report

Generated: 2026-07-03

## Scope

This report audits repetitive, fragile, or failure-prone JavaScript in `resources/views/consultations/show.blade.php`.

## Summary

The consultation page has a high JavaScript maintenance risk. The main root cause is not one broken function; it is the architecture:

- One Blade file owns rendering, route config, modals, AJAX behavior, form serialization, dynamic row templates, local storage, summary refresh, section refresh, select loading, pattern search, ICD search, previous-visit preview, and global function exposure.
- Inline event handlers require functions to be placed on `window`.
- Dynamic section refreshes require manual rebinding after DOM replacement.
- AJAX submission patterns are duplicated across page sections and modals.

The current implementation has some good defensive work, including an AbortController for page listener cleanup and `data-consultation-bound` style binding guards. Those help, but they are compensating for the monolithic inline-script structure.

## Inventory

Observed in `resources/views/consultations/show.blade.php`:

- File length: about 4,470 lines.
- Inline script starts around line 2874 and runs to the end of the file.
- 162 JS/event-related touchpoints were found by searching for script tags, AJAX forms, `fetch`, inline handlers, DOM selectors, localStorage, and Bootstrap modal APIs.
- Multiple forms use `data-ajax-form`.
- Multiple forms also use inline `onsubmit`.
- Several controls use inline `onclick` or `onchange`.
- Duplicate DOM id patterns were found:
  - `id="investigationDeptSelect"`
  - `id="{{ $diagnosis->id }}"`

## Evidence

Inline event handlers:

- Complete/cancel/transition buttons use inline `onclick` confirmation handlers.
- Clinical forms use `onsubmit="saveTabBeforeSubmit(...)"`.
- Investigation department select uses `onchange="loadInvestigationServices(this.value)"`.
- Procedure department select uses `onchange="loadProcedureServices(this.value)"`.
- Lab request department select uses `onchange="loadLabReqItems(this.value)"`.
- Dynamic free-text item markup injects inline `onclick`.

Central script responsibilities include:

- Global config and translations.
- Session drawer handling.
- Current route input injection.
- AJAX form binding.
- Section refresh.
- Summary refresh.
- Edit/delete entry handling.
- Investigation service loading.
- Procedure service loading.
- Lab request modal.
- Prescription rows and calculation.
- Previous visit preview.
- Pattern apply/search.
- Complaint/diagnosis suggestion search.
- ICD Select2 initialization.
- Follow-up filtering.
- Global exposure of functions for inline handlers.

## Root Causes

### 1. Blade is being used as the JavaScript module boundary

The page mixes markup, PHP, route generation, translated strings, JSON config, and executable JavaScript. This makes the page easy to add to, but hard to reason about.

Impact:

- Harder to lint and unit-test frontend behavior.
- Higher chance of variable redeclaration problems after partial reloads.
- Route and permission state are scattered across HTML and script.

### 2. Inline handlers force global functions

Inline handlers such as `onchange="loadProcedureServices(this.value)"` and `onsubmit="saveTabBeforeSubmit(...)"` require those functions to exist on `window`.

Impact:

- Functions cannot remain private to a module.
- Refactors become risky because Blade markup depends on exact global names.
- Re-rendering or script re-execution can create name conflicts.

### 3. Dynamic DOM refresh requires rebinding

The page refreshes consultation sections after AJAX actions. Replacing HTML invalidates event listeners inside those sections, so the script manually binds and rebinds.

Impact:

- Some controls work on initial page load but fail after refresh.
- Newly inserted controls can miss listeners unless every refresh path calls the same setup functions.
- Binding guards reduce duplicate listeners but increase complexity.

### 4. AJAX logic is repeated instead of centralized

There are multiple submission patterns:

- Generic `data-ajax-form` handler.
- Special modal submit handlers.
- Standard POST forms with inline confirmation.
- Dynamic rows with custom submit preparation.

Impact:

- Error display, button disabling, loading state, toast display, and redirect handling vary by section.
- Duplicate-submit protection is uneven.
- User-facing failure messages are inconsistent.

### 5. Page state is stored in several places

The page uses hidden inputs, global variables, localStorage, DOM dataset attributes, route query state, and server-rendered selected-route variables.

Impact:

- Selected route/session bugs are more likely.
- A form may submit with stale route context if hidden input injection misses it.
- Debugging is difficult because truth is spread across browser state and server-rendered state.

## High-Risk Areas

| Area | Risk | Priority |
| --- | --- | --- |
| Procedure department/service picker | A JS binding failure prevents requesting procedures. This was already observed by the user. | P0 |
| Investigation/lab modal | Multiple department/service loaders and modal submit paths can lose state. | P0 |
| Prescription dynamic rows | Complex client-side row generation and calculation can submit malformed or duplicate items. | P1 |
| Section refresh | Replaced DOM can lose listeners or stale hidden route values. | P0 |
| Inline confirmation and submit handlers | Frontend-only protection is inconsistent and bypassable. | P1 |
| Duplicate DOM ids | Selectors can target the wrong element or only the first matching element. | P1 |

## Duplicate and Repetition Patterns

### Repeated form lifecycle

Many sections need the same lifecycle:

1. Save active tab.
2. Ensure current consultation route.
3. Disable submit.
4. POST with CSRF.
5. Render validation errors.
6. Refresh the section.
7. Refresh the summary.
8. Rebind controls.
9. Restore submit state.

This should be one helper, not section-specific code plus inline fallbacks.

### Repeated option loading

Investigation, lab, procedure, and follow-up flows all load options based on a selected department/service/category.

They should use one select-loader utility:

- Source select.
- Target select/container.
- Endpoint builder.
- Placeholder text.
- Empty state text.
- Error text.
- Optional response mapper.

### Repeated modal submission

Lab request, route-to-department, send-session, follow-up, and preview modals each manage their own submit/display behavior. They should use a shared modal form helper.

## Recommended Frontend Architecture

### Target structure

Create a real page module:

- `resources/js/pages/consultation-show.js`
- `resources/js/consultation/ajax-forms.js`
- `resources/js/consultation/route-context.js`
- `resources/js/consultation/section-refresh.js`
- `resources/js/consultation/requests.js`
- `resources/js/consultation/prescriptions.js`
- `resources/js/consultation/patterns.js`
- `resources/js/consultation/previous-visits.js`
- `resources/js/consultation/summary.js`

Blade should provide only:

- Markup.
- `data-*` attributes.
- One JSON config script block, preferably `type="application/json"`.
- Server-rendered initial state.

JavaScript should own:

- Event delegation.
- Form submission.
- Dynamic select loading.
- Modal form lifecycle.
- Duplicate-click prevention.
- Section refresh and re-initialization.

### Near-term bridge if a full Vite refactor is too large

Create a single namespaced object:

```js
window.UHMSConsultation = {
  init,
  destroy,
  forms,
  requests,
  prescriptions,
  summary,
};
```

Then replace inline handlers gradually with `data-action` attributes.

This still leaves JS in Blade, but it reduces global-function sprawl and gives the page one lifecycle.

## Recommended Markup Direction

Replace inline handlers like:

```html
<select onchange="loadProcedureServices(this.value)">
```

with:

```html
<select data-consultation-action="load-procedure-services">
```

Replace inline submit handlers like:

```html
<form onsubmit="saveTabBeforeSubmit('procedures-section')">
```

with:

```html
<form data-ajax-form="procedures" data-tab="procedures-section">
```

Replace injected inline button handlers with delegated actions:

```html
<button type="button" data-action="remove-free-item" data-target="freeItem1">
```

## Testing Recommendations

Add browser or feature tests for:

- Page loads without JS console errors.
- Procedure department selection loads services.
- Procedure request submits successfully.
- Investigation department selection loads services.
- Lab request modal submits and keeps consultation route context.
- Prescription row add/remove and submit.
- A refreshed section still has working edit/delete/add controls.
- Double-click submit creates only one record.

Add static checks:

- Fail if duplicate ids appear in consultation view.
- Fail if new inline `onclick`, `onchange`, or `onsubmit` are added to consultation view.
- Lint page modules once JS is moved into `resources/js`.

## Priority Fix List

P0:

- Move route/session context into one shared helper.
- Add server-side idempotency before relying on frontend button disabling.
- Replace procedure and investigation inline `onchange` handlers with delegated listeners.
- Ensure all AJAX refreshed sections re-initialize from one lifecycle.

P1:

- Remove inline `onsubmit` and `onclick` from consultation page.
- Split modal form logic into reusable helpers.
- Remove duplicate DOM ids.
- Move dynamic prescription/procedure/lab UI code into page modules.

P2:

- Convert page config to JSON script data.
- Add JS unit tests for pure helpers.
- Add frontend smoke tests in CI.

## Conclusion

The repetitive JavaScript is caused by an inline monolith plus mixed event binding, not by one isolated bug. The fastest safe path is to introduce one page lifecycle and one AJAX/form helper, then move section behavior out of Blade in phases.


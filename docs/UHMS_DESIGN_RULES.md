# UHMS Design Rules

Use these rules for new screens and when touching old screens.

1. Page headers use `.uhms-page-header` or the existing `.page-header`, with the title on the left and primary actions on the right.
2. Cards use the shared 8px radius, light border, and low shadow from `build/css/uhms-design-system.css`.
3. Use one main card for each table, form, or operational panel. Avoid stacking decorative cards inside other cards.
4. Use Tabler icons in action buttons and keep button text short: `New`, `Filter`, `Receive`, `Print`, `Cancel`.
5. Use `.btn-primary` only for the main action on a screen. Use outline buttons for navigation and secondary actions.
6. Tables should use `table-hover`, compact headers, right-aligned money columns, and status badges for workflow state.
7. Forms should use consistent labels, Bootstrap validation states, and page-level flash alerts.
8. Billing and accounting screens must keep patient revenue in `payments`; manual income and expenses stay in `financial_entries`. Reports may combine them, but should label them separately to avoid double counting.
9. Cash payments should be collected during an open cashier shift so handover, daily collection, and reconciliation stay aligned.
10. Calendar and queue screens should be dense, scannable, and workflow-first: visible dates, status color, patient name, time, and next action.
11. Prefer shared CSS/layout fixes over per-page rewrites. Do not add page-specific JavaScript or heavy libraries for visual consistency.
12. Active and legacy Blade layouts must load `build/css/uhms-design-system.css` after the template stylesheet so old and new pages share the same base rules.
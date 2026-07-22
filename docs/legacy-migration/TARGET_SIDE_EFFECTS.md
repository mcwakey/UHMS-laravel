# Target runtime side effects

## Critical conclusion

Disabling Eloquent events alone is insufficient. Many dangerous effects are explicit service calls, dispatched events, after-commit observer work, queued jobs, external integrations, or scheduled commands.

Phase 1B actual-target inspection explicitly found zero database triggers, zero scheduled events and zero stored routines; the table catalogue also found zero views. The regenerated target manifest is the authority for these installed counts. The principal known risk is application runtime: 26 activity-logged models plus observers/listeners/schedulers and explicit billing, accounting, stock, bed, queue, notification and integration calls. Historical persistence must therefore be isolated from the normal application runtime, not merely from database triggers.

## Side-effect register

| Area | Confirmed effect | Historical-import risk |
|---|---|---|
| Activity/audit | Spatie `LogsActivity` on major models; explicit/queued activity service; optional Slack/SIEM/archive forwarding | Creates current-time/user audit that looks contemporaneous and can leave the system |
| Visit observer | Materializes visit payment policy/history by default | Invents current financial-policy history |
| Invoice/payment observers | Write audit and mark financial clearance stale after commit | Mutates visit finance state after loading |
| Notifications | Database notifications/digests resolved to active roles/departments | Alerts current staff about historical events |
| SMS | Appointment reminders and payment receipt listener can queue real SMS | Patient contact/privacy incident |
| Visit workflow | Status/pathway logs, queues, current timestamps, insurance voiding | Rewrites history and creates active work |
| Consultation | Route logs, pause/activation, queues, medical records, billing, contributors, locks | Creates derived/duplicate records and present-day state |
| Admission/discharge | Bed/bay occupancy, visit state, charges, history, events/notifications | Corrupts live capacity and finance |
| Billing/AR/claims | Prices, invoices, usages, receivables, allocations, accounting posting | Double counting or use of current tariffs/policies |
| Service rendering | Work items, pathway events, destination notifications | Sends historical work to departments |
| Stock/pharmacy | Movement, balance, valuation, GL posting, dispensing/MAR, low-stock event | Changes current inventory and finance |
| Procurement | GRN, movement, PO state, supplier ledger/payable, budgets, GL | Creates current liabilities/stock |
| Files | Patient avatar store/delete | Unapproved filesystem mutation/PHI handling |
| Schedulers | Auto-close, reminders, overdue/stale checks, blood expiry, digests, reconciliation, journey jobs | Acts on incomplete temporary imports |

## Evidence locations

- Observer/listener registration: `app/Providers/AppServiceProvider.php`.
- Operational schedules: `bootstrap/app.php` and `routes/console.php`.
- Patient/visit behavior: `app/Services/PatientService.php`, `VisitService.php`, and visit workflow/pathway services.
- Billing/payment: `BillingService.php`, `InvoiceService.php`, `PaymentService.php` and observers.
- Stock/procurement: `ProductStockMovementService.php`, `PharmacyService.php`, `ProcurementService.php`.
- Admission/consultation: `AdmissionService.php`, `ConsultationRouteService.php`, and consultation workflow services.

## Required future controls

1. A migration mode that fails closed unless side-effect isolation is active.
2. A migration-specific persistence layer that never calls operational creation/transition services for historical rows.
3. Explicit queue, scheduler, SMS/email/provider, notification, audit-forwarding, and accounting/stock posting gates for commit windows.
4. Verification after each chunk that prohibited tables/queues/external outboxes did not change.
5. Clearly labelled migration audit records written by the migration framework itself, separate from source historical facts and operational activity logs.
6. Controlled cutover restoration with reconciliation of active appointments, queues, beds, reminders, sequences, finance, and stock before integrations resume.

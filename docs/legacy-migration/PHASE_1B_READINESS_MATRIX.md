# Phase 1B readiness matrix

## Assessment basis

Source and target evidence remain the Phase 1B sanitized catalogues, aggregates, fingerprints, installed-target capture, repository analysis, and drift report. The direct project-owner decision dated 2026-07-21 resolves every listed business-policy prerequisite.

“Policy approved; Phase 2 specification pending” means detailed mapping may begin but the executable transformation/exception/reconciliation contract does not yet exist. “Ready for detailed mapping” never means ready to implement or run an importer.

| Migration domain | Source evidence complete | Target evidence complete | Required decisions approved | Transformation rules approved | Exception rules approved | Reconciliation rules approved | Incremental strategy approved | Ready for detailed mapping | Remaining blockers |
|---|---|---|---|---|---|---|---|---|---|
| Organisation, departments, reference catalogues | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Natural keys, deduplication, value/sentinel crosswalks, counts, extraction contract |
| Staff identity and historical attribution | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Verified employment crosswalk, disabled historical identity, unknown-actor contract |
| Patient identity and registration | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Number/alias rules, required-field validity, quarantine chains, identity-review handoff |
| Emergency contacts and demographic children | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Column maps, privacy/retention application, orphan/chain outcomes |
| Patient insurance and eligibility | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Provider crosswalk, membership consolidation, field-specific dates/statuses |
| Appointments and scheduling | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Required-field derivations, numbering, historical validation, reminder suppression |
| Visits, triage, workflow history | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Value/date/sentinel maps, service-only invariant handling, side-effect contract |
| Consultation routes and medical records | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Evidence-backed grouping/clinician rules and logical uniqueness validation |
| Clinical history, diagnoses, treatments | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | RTF/plain/catalogue rules, provenance, grouping, retention |
| Investigations and clinical results | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Service/result mappings, result-not-evidenced state, high-volume extraction |
| Admissions, beds, discharge history | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Corroboration predicates, dates, active admission/bed invariants, isolation |
| Billing and source receivable facts | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Decimal policy approved; contract pending | Policy approved; per-table design pending | Yes | Four-field equation, 0.01 exception gate, opening/anti-double-count contract |
| Insurance claims | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Decimal 0.01 policy approved; contract pending | Policy approved; per-table design pending | Yes | Payer/status/date maps, shared claim equation and posting gate |
| Payments, allocations, accounting | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Opening/zero-start policy approved; contract pending | Policy approved; per-table design pending | Yes | Exact approved openings; unsupported histories remain empty; GL posting prohibited |
| Pharmacy products and catalogues | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Product/unit/location identities, duplicates, source/reference crosswalks |
| Stock, batches, procurement | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Opening/expiry policy approved; contract pending | Policy approved; per-table design pending | Yes | Opening equation, negatives, expiry quarantine, batch identity, disposition |
| Privacy, retention, historical audit | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Migration-audit design, aggregate-only notification proof, scope fingerprint gate |
| Incremental synchronization and cutover | Yes | Yes | Yes | Policy approved; Phase 2 specification pending | Policy approved; codes/SLA pending | Policy approved; contract pending | Policy approved; per-table design pending | Yes | Extraction contracts, restricted account, isolation runbook, thresholds, rollback |

## Overall determination

- Direct-owner governance resolution: **complete** for Phase 1B business policy; no workshop occurred.
- Detailed mapping: **may begin for every listed domain**, following the ordered Phase 2 sequence.
- Patient-pilot mapping contract: **may begin after its prerequisite reference, staff, patient, alias/contact, and insurance maps are produced**.
- Importer implementation: **blocked** until column mappings, transformation specifications, exception codes, reconciliation contracts, extraction rules, and the reviewed migration foundation exist.
- Commit-mode execution, production writes, incremental runtime, and cutover: **not authorized**.

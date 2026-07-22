# Legacy database inventory

## Source baseline

**Confirmed:** `uuhms` is the stakeholder-approved Classic schema. It is MariaDB 10.4.32 with a database default of `utf8mb4_general_ci`, 55 InnoDB base tables, 0 views, 479 columns, 1,548,058 exact rows, about 445.92 MiB of data and 18.13 MiB of indexes. Phase 1B confirms all installed tables and 231 character columns use `latin1_swedish_ci`, despite the database default.

There are no declared foreign keys. Fifty-three tables have a single-column primary key; 52 are auto-incrementing. `claims.CLAIM_ID` is a non-auto-increment primary key. `serv_options_cri` and `serv_options_out` have no primary key or index. Most inferred relationship columns are unindexed.

The configured database account is unsafe for migration use because it has broad schema-level DDL/DML privileges and server/session defaults are writable. Phase 1B capture forces a repeatable-read, session read-only transaction and runs only fully qualified metadata/aggregate `SELECT` queries, but a database-enforced SELECT-only account remains mandatory for migration execution.

## Phase 1B reproducible evidence

The evidence under `docs/legacy-migration/evidence/` now contains the full table/column/index contract, tested relationship predicates, versioned queries with query/binding/result hashes and timestamps, critical-field/date/finance/stock aggregates, a per-table incremental capability matrix, and separate structural-schema and snapshot fingerprints. Binary logging/GTID coordinates are unavailable on this server. The command records and verifies its REPEATABLE READ, session-level read-only transaction controls without claiming a reusable log position or treating `tx_read_only` alone as proof of snapshot state.

## Classification legend

- **Reference/config**, **Master**, **Transaction/history**, **Operational/transient**, **Dormant/empty**, or **Unresolved** is the base disposition class.
- Suffixes such as `PHI`, `financial`, `keyless`, or `mutable state` are risk/structure qualifiers, not additional disposition states.
- Reference/config records reconcile by approved natural keys; source PKs never become target PKs.
- Transaction/history requires complete dependencies and reconciliation. Operational/transient normally starts empty or becomes clearly labelled history only with approval.

## Complete 55-table classification

| Legacy table | Rows | Classification | Preliminary disposition |
|---|---:|---|---|
| `accounts` | 1 | Transaction/history | Financial scope decision; map to approved journal/opening strategy, not directly |
| `acc_petty` | 1 | Unresolved financial config | No direct approved mapping; finance decision |
| `acc_titles` | 31 | Reference/config | Candidate account categories/chart mapping |
| `bank` | 1 | Reference/financial master | Candidate bank-account setup; balances require finance approval |
| `billing` | 111,731 | Transaction/history | Candidate invoice/item/payment snapshot graph; exact reconciliation gate |
| `claims` | 31,892 | Transaction/history | Candidate claims graph after patients/visits/insurance/invoices |
| `claims_diagnosis` | 57,776 | Transaction/history | Claim diagnosis items; quarantine invalid links |
| `claims_prescriptions` | 112,152 | Transaction/history | Claim medication items; quarantine invalid links |
| `claims_procedures` | 3,648 | Transaction/history | Claim procedure items |
| `claims_scan_lab` | 3 | Transaction/history | Claim investigation/procedure items; semantic mapping required |
| `claims_specialty` | 9 | Reference/config | Candidate specialty crosswalk |
| `patients` | 16,950 | Master/PHI | In-scope pilot subject to identity/required-field policy |
| `attendance` | 51,927 | Transaction/history/PHI | Candidate visits/admissions/pathway facts; status/date/orphan decisions |
| `appointement` | 1,364 | Transaction/history/PHI | Candidate historical appointments; `AppDate` supplies a populated non-midnight datetime, plus optional attendance link |
| `insurance` | 31,307 | Master child/PHI | Candidate patient insurance; member/date/provider exception policy |
| `beds` | 18 | Reference plus mutable state | Map bed identities/config; rebuild current occupancy at cutover |
| `consult_complaints` | 65,244 | Clinical history/PHI | Candidate complaints; catalogue/free-text precedence required |
| `consult_diagnosis` | 123,940 | Clinical history/PHI | Candidate diagnoses; catalogue links largely unreliable |
| `consult_history` | 34,532 | Clinical history/PHI | Candidate HOPC/history; plain/RTF precedence required |
| `consult_prescriptions` | 210,902 | Clinical/medication history | Candidate prescriptions/orders only after visit/product/actor maps |
| `consult_procedures` | 121 | Clinical history | Candidate procedures; status/result semantics incomplete |
| `consult_scan_lab` | 72,680 | Clinical investigation history | Candidate investigations/orders/results; actor/link gaps |
| `consult_services` | 60,260 | Clinical/service history | Candidate service renderings or historical services; semantic decision |
| `consult_treatment` | 1,926 | Clinical history/PHI | Candidate treatment plans; plain/RTF precedence required |
| `nurses_note` | 29 | Clinical history/PHI | Candidate nursing notes |
| `treatment` | 69 | Clinical medication administration | Candidate MAR/treatment history; mapping decision |
| `vitals` | 60,803 | Clinical observations/PHI | Candidate vitals; parse strings without losing raw values |
| `mat_family` | 0 | Dormant/empty | Exclude unless product owner identifies required configuration |
| `mat_obstetrics` | 0 | Dormant/empty | Exclude unless product owner identifies required configuration |
| `services` | 203 | Reference/config | Candidate service catalogue/pricing crosswalk |
| `serv_group` | 155 | Reference/config | Candidate investigation service groups |
| `serv_criterias` | 200 | Reference/config | Candidate investigation criteria/analytes |
| `serv_options_cri` | 20 | Reference/config, keyless | Candidate criterion options; deduplicate by approved natural key |
| `serv_options_out` | 32 | Reference/config, keyless | Candidate result options; deduplicate by approved natural key |
| `serv_results` | 460,758 | Clinical result history/PHI | Candidate structured results; weak order links and no timestamps |
| `list_causes` | 18 | Reference/config, dormant-like | No reliable parent links; map only if clinically approved |
| `list_complaints` | 8,924 | Reference/config | Candidate complaint catalogue, likely requires deduplication |
| `list_diagnosis` | 5,948 | Reference/config | Candidate ICD/diagnosis crosswalk; duplicate/missing codes expected |
| `list_disorder` | 1 | Dormant/incomplete reference | Likely exclude unless approved |
| `list_procedures` | 768 | Reference/config | Candidate procedure catalogue/pricing crosswalk |
| `list_signs` | 2 | Dormant/incomplete reference | No valid inferred parents; likely exclude |
| `list_symptoms` | 6 | Unresolved reference | Long-text parent identifier prevents reliable FK interpretation |
| `medicine` | 4,303 | Reference/product master | Candidate product/drug/generic catalogue; many missing names/codes |
| `med_pharm` | 8,360 | Mutable stock snapshot | Source snapshot/opening-position decision; no complete movement ledger; duplicate product rows and negatives |
| `med_store` | 8,356 | Mutable stock snapshot | Source snapshot/opening-position decision; no complete movement ledger; duplicate product rows and negatives |
| `batch` | 253 | Inventory lot history/master | Candidate batches after products/suppliers; expiry flags unreliable |
| `request` | 146 | Procurement/stock transaction | Candidate requisitions only if approved; quantity/actor exceptions |
| `suppliers` | 2 | Supplier master plus balance | Candidate supplier natural-key map; balances require finance decision |
| `departements` | 18 | Reference/config | Candidate department crosswalk; target is richer |
| `users` | 86 | Staff identity/access source | Map identity/attribution only; never migrate passwords or blanket permissions |
| `hospitalinfo` | 1 | Configuration | Manually reconcile approved facility metadata; exclude signatures/assets by default |
| `notifications` | 141 | Operational/transient/PHI-capable | Exclude unless retention owner approves historical labelled import |
| `gens` | 2 | Sequence/config | Do not migrate as target keys; retain only as source-number evidence |
| `sett_ocuupation` | 0 | Dormant/empty | Exclude |
| `sett_private` | 38 | Insurance reference/config | Candidate private-provider crosswalk |

## Record-volume concentration

The largest tables are `serv_results` (460,758), `consult_prescriptions` (210,902), `consult_diagnosis` (123,940), `claims_prescriptions` (112,152), and `billing` (111,731). The main storage consumers are `consult_scan_lab`, `attendance`, `serv_results`, `consult_prescriptions`, and `consult_history`. All require chunked extraction and stable deterministic ordering; high-volume relationship columns are mostly unindexed.

## Timestamp capability

Twenty-three tables have no timestamp/date column: `acc_petty`, `acc_titles`, `claims_specialty`, `departements`, `gens`, `hospitalinfo`, all `list_*` tables, `med_pharm`, `med_store`, `services`, `serv_criterias`, `serv_group`, both `serv_options_*`, `serv_results`, `sett_ocuupation`, and `sett_private`.

Nine timestamps update automatically on row changes: `batch.BatchDate`, `beds.BedDate`, `billing.PayDate`, `consult_scan_lab.SclUpdate`, `notifications.RecDate`, `patients.EditDate`, `request.ReqDate`, `suppliers.RegDate`, and `vitals.VitDate`. These are not automatically reliable creation timestamps or simple incremental watermarks.

## Sensitivity

- PHI: patients, insurance, encounters, appointments, beds, clinical/claims/billing/results/maternity records.
- Staff/security: users, including password material that was not inspected.
- Financial: accounts, billing, claims, bank, suppliers, catalogue prices, inventory valuation.
- Free-text PHI risk: notifications, request descriptions, billing descriptions, and clinical text.

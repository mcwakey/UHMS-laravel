# Phase 14R.8 — O&G/Maternity Snapshot Completion-Occurrence Identity Hardening (Report)

**Status:** ✅ Implemented.
**Objective:** close confirmed risk **P2** — `CLOSED_BY_PHASE_14R_8`.
**Scope:** completion identity only. No billing, no clinical fields, no UI redesign, no flag change.
**Companions:** `..._DESIGN.md` · `..._TEST_MATRIX.md` · `..._ROLLBACK.md`

---

## 1. Root cause — confirmed

Snapshot idempotency identity was a **second-granular** string:

```php
sprintf('route:%d@%s', $consultation->id, $completedAt->utc()->format('Y-m-d\TH:i:s\Z'))
```

matched against the unique index `(consultation_route_id, completion_reference)`.

A reopen-and-recompletion inside the same wall-clock second computed a byte-identical reference, so
`captureForCompletion()` found the existing row and returned it. The second completion was genuine
and clinically distinct, and its state was captured nowhere.

**Confirmed, not assumed:** reproduced under a frozen clock in 14R.7, and the fix was verified by
watching that same test change behaviour.

The failure was **record-incompleteness**, never corruption — v1 was always intact. That
distinction shaped the whole design: no existing data needed repair, only the identity needed
replacing.

---

## 2. Existing completion flow (audited before changing anything)

`ConsultationRouteService::completeRoute()` early-returns for `COMPLETED`/`CANCELLED` routes (the
*intended* idempotency, unrelated to P2), then in one transaction writes the status, captured the
14R.6 snapshot, and wrote the route + activity logs.

**Nothing existing truthfully represented one completion occurrence.** Rejected on evidence:

| Candidate | Why rejected |
| --- | --- |
| `visit_consultation_route_logs` | Mutable, multi-purpose (start/pause/resume/complete) |
| `activity_log` (Spatie) | Optional, async-capable (`audit_streaming.async_writes`), suppressible — spec-disallowed, and rightly |
| Higher-precision timestamps | Narrows the window, never closes it; makes residual failures rarer and harder to detect |
| `MAX(version)+1` | Races without a lock, and answers the wrong question |

The spec's preferred path (reuse an existing durable event) was genuinely attempted first and
closed off on the evidence above.

---

## 3. Authoritative identity chosen

A **completion occurrence**: one immutable row per genuine `→ COMPLETED` transition, identified by
a server-generated **ULID**.

```
ACTIVE → occurrence 1 (ULID A) → COMPLETED → snapshot v1 (occ:A)
       → REOPENED
       → occurrence 2 (ULID B) → COMPLETED → snapshot v2 (occ:B)
```

`completed_at` is retained on the occurrence for humans and audit — **never** as identity. That
single sentence is the phase.

New idempotency key:

```
maternity-summary-snapshot + consultation route + completion occurrence + schema version
```

stored as `completion_reference = "occ:{ULID}"`.

---

## 4. Schema / migrations

**`2026_07_28_000001_create_consultation_completion_occurrences_table.php`** — the ledger:
`consultation_route_id` (FK cascade), `visit_id` (FK nullOnDelete), `occurrence_uid` (ULID),
`occurrence_number`, `previous_occurrence_id` (self-FK), `from_status`, `to_status`, `completed_at`,
`completed_by`, `metadata`, `created_at` only.

Explicit short index names — MariaDB's 64-char limit makes generated names a real hazard:

* `cco_uid_unique` — UNIQUE (`occurrence_uid`)
* `cco_route_number_unique` — UNIQUE (`consultation_route_id`, `occurrence_number`)

**`2026_07_28_000002_link_maternity_snapshots_to_completion_occurrences.php`** — one **nullable**
`completion_occurrence_id` FK on `consultation_maternity_snapshots`. Deliberately **not**
backfilled. The pre-existing unique index on `(consultation_route_id, completion_reference)` is
retained unchanged.

Both migrations declare `down()`, and 000002's drops the FK by its **explicit** name (see §15a/H2 —
the original used the conventional 65-char name that was never created). No existing row was
rewritten by either. SQLite cannot reverse 000002 and now says so explicitly rather than failing
obscurely; production is MySQL/MariaDB, where it reverses cleanly.

---

## 5. Services changed

| File | Change |
| --- | --- |
| `app/Services/Consultation/ConsultationCompletionOccurrenceService.php` **(new)** | `record()` allocates `occurrence_number = previous + 1` and a fresh ULID from a **locking** read (`lockedLatestFor()`); on unique violation re-reads the winner and requires it to be **strictly newer** before returning, else rethrows. Plus `latestFor()`, `currentFor()`, `historyFor()`. |
| `app/Models/ConsultationCompletionOccurrence.php` **(new)** | Append-only via `ImmutableClinicalSnapshot`; `snapshotReference()`, `isLegacyReference()`. |
| `app/Services/ConsultationRouteService.php` | Re-reads the route **under lock as the first statement inside the transaction** and re-checks status before doing anything; then mints the occurrence after the status write and passes it to snapshot capture. |
| `.../ConsultationMaternitySnapshotService.php` | `completionReference($route, ?$occurrence)` returns `occ:{ulid}`; new `legacyCompletionReference()` preserves the old format verbatim. |
| `app/Models/ConsultationMaternitySnapshot.php` | `completion_occurrence_id` fillable + relation + `usesLegacyCompletionReference()`. |
| `app/Console/Commands/ObgynMaternityPilotPreflightCommand.php` | New read-only `completion identity` area (7 checks). |

**The occurrence is minted in exactly one place.** The snapshot service consumes an occurrence and
never creates one — that is what keeps retries idempotent.

---

## 6. Transaction and idempotency design

```php
DB::transaction(function () {
    $locked = VisitConsultationRoute::whereKey($route->id)->lockForUpdate()->first();
    if (already COMPLETED or CANCELLED) { return $this->freshRoute($route); }   // H1 guard

    $route->update([... COMPLETED, completed_at => now() ...]);
    $occurrence = $this->recordCompletionOccurrence($route, $user, $from);      // locking read + insert
    $this->captureMaternitySnapshot($route, $user, $occurrence);
    $this->log(...);
});
```

Order is deliberate: an occurrence can only exist for a completion that really happened, and a
snapshot can never mint its own identity. One transaction throughout — a capture failure rolls the
completion back rather than leaving a completed consultation with a half-written medico-legal
record.

Three guards, in order of who catches what:

1. **Status re-check under the route lock**, inside the transaction — stops a double-submit from
   minting a second occurrence for a completion that never happened (H1).
2. **Locking read of the previous occurrence** — forces a current read rather than a read-view read,
   so allocation cannot be fooled by a stale snapshot of the ledger (H3).
3. **The composite unique index** — the final guard. A violation is never treated as success on
   trust: the winner is re-read under lock and must be **strictly newer** than what this call saw
   (M1).

---

## 7. Legacy compatibility

Pre-14R.8 snapshots keep `route:{id}@{timestamp}` and `completion_occurrence_id = NULL`. **Not
backfilled** — fabricating occurrence rows for historical completions would invent clinical events
that never happened, which in a medico-legal table is worse than the original defect.

Mixed history (legacy v1 + occurrence-based v2) reads, verifies and orders normally.

---

## 8. Hash compatibility

**Decision: occurrence identity is envelope metadata, outside the hashed payload.**

The hash covers `ConsultationMaternitySummaryProjection::toCanonicalArray()` — clinical content
only. `completion_occurrence_id` is a column, not a payload key.

* Every existing hash is **byte-identical** and still verifies.
* No rehash, no migration of hashes, **no `SCHEMA_VERSION` bump** (still `14R.6.1`) — the clinical
  contract did not change.
* The hash keeps meaning exactly one thing.

Still tamper *evidence*, not non-repudiation — unchanged from 14R.6.

---

## 9. Test results

**Primary suite:** `ConsultationSnapshotCompletionIdentityPhase14R8Test` — **29 tests, 130
assertions, all passing**, covering the 41 specified checks plus one added after independent review.
Full mapping in the test matrix.

| Group | Checks | Result |
| --- | --- | --- |
| Same-second identity | 1–6 | PASS — v1/v2/v3 under **one frozen second**; distinct ULIDs; v1 byte-identical; all hashes verify; `gravida` `[2,6,7]` preserved per version |
| Intended idempotency | 7–11 | PASS — 3 repeat completions → 1 occurrence/1 snapshot; 5 request replays → no duplicate; 10 redelivered captures → 1 snapshot |
| Concurrency | 12–15 (+12a) | PASS — forced racing insert rejected by the unique index; **double-submitted completion yields one occurrence** (12a, added after review — it caught a real regression); version numbers `[1..5]` unique/monotonic within one second |
| Failure/retry | 16–20 | PASS — total rollback on failure (route not left COMPLETED); recovery resumes against the **same** occurrence; raw hash tamper fails closed |
| Legacy | 21–25 | PASS — legacy row readable/verifiable/ordered; not backfilled; update+delete rejected on **both** tables |
| Workflow | 26–38 | PASS — summary/print share one payload; permission denial empties context; 0 invoice items; 0 billing events; 0 order-set writes; 0 flag changes |
| Performance | 39–41 | PASS — history ≤2 queries for 4 versions; preview adds 0 queries on rebuild; feature-off completion issues **0** maternity queries |

### Inverted 14R.7 suite — stated plainly

`ConsultationSnapshotSameSecondRiskPhase14R7Test` was written to **prove the defect existed**. Its
assertions were **inverted** (5 tests, 11 → 17 assertions): `assertCount(1, …)` "the collapse" became
`assertCount(2, …)` "the collapse cannot happen".

This is flagged explicitly because inverting a test can look like weakening one. It is not — the
file now asserts strictly more, and the original defect is preserved in its class docblock.

---

## 10. Query-count regression

| Path | Measured |
| --- | --- |
| Completion, flags off | **0** maternity queries; ledger costs **≤2** (1 read + 1 insert) |
| Snapshot history, 4 versions | **≤2** queries — constant in version count |
| Preview rebuild | **0** additional queries (memoised) |

---

## 11. Focused regression

| Suite group | Result |
| --- | --- |
| P2 closure + 14R.6/14R.6.1 snapshot, summary, history, print, readiness, billing-dedup, reconciliation | **141 passed** |
| Gynaecology, obstetrics, handoffs 14R.3–14R.5.1, Admission & Emergency integration | **171 passed, 1 skipped** |
| Consultation route/session, follow-up/next-patient, workspace, preview parity, bridge, order sets, visit routing, `Consultations/` | **319 passed, 31 failed** — see below |

### The 31 failures are pre-existing — proven twice, not asserted

I ran the identical commands with **all** Phase 14R.8 changes stashed (tracked modifications
stashed, new files moved aside; `docs/prompt.md` never touched):

| Run | Result |
| --- | --- |
| 8-suite group, with 14R.8 | 26 failed, 314 passed (3472 assertions) |
| 8-suite group, **14R.8 stashed** | **26 failed, 314 passed (3472 assertions)** — identical failure set |
| `ConsultationFollowUpAndNextPatientTest`, with 14R.8 | 5 failed, 5 passed |
| `ConsultationFollowUpAndNextPatientTest`, **14R.8 stashed** | **5 failed, 5 passed** — identical failure set |

Both failure sets diff clean (only trailing whitespace). They are workspace/visit-list/queue
rendering failures unrelated to completion identity.

The follow-up/next-patient suite was **added to the regression after independent review** flagged
it, precisely because `ConsultationNextPatientService::openNext()` calls `completeRoute()` — the
method this phase changed. Its 5 failures are pre-existing, and the one test that does exercise the
completion path (`complete_and_open_next_patient_completes_current_route_then_opens_next`) passes.

**No new failure is attributable to this phase.** These 31 are a pre-existing condition of the
branch and remain open; this phase does not fix them and does not claim to.

---

## 12. Billing non-posting proof

Asserted directly in check 34–36: after two completion cycles, `invoice_items` = **0**, maternity
billing event tables = **0**, order-set tables = **0**. `postForSource()` remains non-posting and
was not touched. `billing.maternity_billing.enabled` and `..._auto_post` remain `false`.

---

## 13. Feature-flag state

**Zero flags changed.** No committed environment file gained an enabled default. Preflight confirms
all twelve tracked flags still `false`. Check 38 asserts flag values are identical before and after
completion.

Note recorded from check 31: with the maternity snapshot flag **off**, no snapshot is captured but
the occurrence ledger still records the completion. The ledger is consultation lifecycle
infrastructure, not a maternity feature — deliberate, and stated so it is not mistaken for leakage.

---

## 14. Preflight

New read-only `completion identity` area — 7 checks, all `PASS` in this environment: ledger present,
identity columns present, both unique indexes installed, ledger append-only, snapshot lineage column
present, legacy compatibility reported, P2 verification suite present.

Overall verdict remains **`WARNING`** — from the two **pre-existing, out-of-scope** items: the
unretargeted order-set items (R4) and the un-supplied environment reconciliation report (P3).

**The same-second identity problem no longer appears as a production-readiness warning.**

`clinician_acceptance: NOT_ASSESSED — this command cannot evaluate clinical usability` — unchanged,
and it must stay that way until a clinician actually signs off.

---

## 15. Wide-suite status — unchanged and honestly reported

`composer test:wide` **still OOMs at ~60% (1403/2336)** against the project's intentional 512 MB
limit in `phpunit.xml`, which `RouteLoadMemoryTest` asserts. This was proven pre-existing in 14R.7
via a stashed A/B run with identical OOM counts.

**It was not re-run for this phase and is not claimed green.** The limit was not raised,
`RouteLoadMemoryTest` was not modified, no baseline was created, and no failure was allowlisted.
R2 remains open as its own infrastructure issue. P2 closure rests on targeted regression, as the
specification requires.

---

## 15a. Independent review — findings and fixes

An independent adversarial reviewer verified all 13 required criteria. It reported **no CRITICAL
findings**, and confirmed no weakened/skipped/allowlisted test, no fabricated clinician sign-off, no
flag change, no billing change, and no logic moved into JavaScript. It also confirmed the inverted
14R.7 suite is a legitimate strengthening rather than a cover-up.

It initially returned **FAIL on three criteria** (2, 7, 8). I verified each independently rather
than accepting or dismissing it. **All three were real defects, and all three are fixed.**

| # | Finding | Verified | Fix |
| --- | --- | --- | --- |
| **H1** | A double-submitted completion minted a **second occurrence and a duplicate v2** with byte-identical content — a ledger row asserting a completion that never happened. The status guard sat *outside* the transaction; the old timestamp identity had accidentally masked this by collapsing both writes. **A regression introduced by this phase.** | Reproduced with a written-first failing test (`2 !== 1`) | `completeRoute()` now re-reads the route **under `lockForUpdate()` inside the transaction** and re-checks status before minting. Covered by check 12a. |
| **H2** | `down()` called `dropConstrainedForeignId()`, which emits Laravel's conventional 65-char FK name. That constraint never existed — `up()` uses `cms_occurrence_fk` *because* the conventional name exceeds MariaDB's 64-char limit. Rollback would have failed on production. | Confirmed by Blueprint source (`dropIndexCommand`: array → generated name, string → verbatim) and by measuring the name at 65 chars | `down()` now drops by the explicit name. **Additionally found:** SQLite cannot reverse this at all (no PRAGMA helps — verified on 3.49.1), so it now throws an explicit, actionable exception instead of a confusing schema error. |
| **H3** | `latestFor()` used a **non-locking** read to allocate `occurrence_number`. Locking the *route* row is not enough: under InnoDB REPEATABLE READ a plain SELECT is served from the transaction's read view. A caller that had already read in the same transaction — `ConsultationNextPatientService::openNext` does — could miss occurrence #1, re-read the same stale view on conflict, and **silently reintroduce P2**. | Confirmed by inspection; the caller path is real | Allocation now uses a private `lockedLatestFor()` with `lockForUpdate()`. Read paths keep the non-locking `latestFor()`. |

Medium findings, also addressed:

* **M1 — the lineage check was tautological.** `latestFor()` is already route-scoped, so
  `$winner->consultation_route_id !== $route->id` could never fire. The winner must now be
  **strictly newer** than the occurrence this call saw, so a non-unique `23000` failure can no longer
  hand back the *previous* occurrence and lose a completion silently.
* **M2 — the ledger is not complete, and the docs said it was.** `EmergencySessionService`
  completes routes via `forceFill()->save()`, bypassing `completeRoute()`. Those routes get no
  occurrence. Pre-existing and out of scope to change; the overclaim is corrected in the model
  docblock, `currentFor()`, and the design doc. P2 is unaffected — snapshots are only captured in
  `completeRoute()`.
* **M3 — deploy-order coupling.** The ledger write is unflagged and on the completion hot path, so
  shipping code ahead of the migration would break every consultation completion. Added as
  **rollout step 0** in the 14R.7 checklist, with the preflight reporting `BLOCKED` when the table
  is missing.
* **M4 — preflight PASS was presence-based.** Rows now say so explicitly: the ledger check reports
  the structure is installed and that *closure is proven by the focused suite, not by this check*;
  the suite check says it verifies the file exists and does not execute it.

Low findings (L1 duplicate-ish concurrency test, L2 self-FK cascade nuance, L3 unrelated
pre-existing failures) are recorded in the test matrix's "Not covered" section rather than silently
dropped. L3 is resolved above by the second A/B run.

---

## 16. Documentation updated

Created: this report, `..._DESIGN.md`, `..._TEST_MATRIX.md`, `..._ROLLBACK.md`.

Updated: 14R.7 report (**follow-up references only** — historical results left verbatim), clinician
pilot guide (fast reopen/recomplete check), pilot results template, integration plan, gap analysis
(risk R8), reconciliation test plan (§10), rollout checklist (migration as step 0).

P2 is marked `CLOSED_BY_PHASE_14R_8` in every one.

---

## 17. Status

| Item | Status |
| --- | --- |
| **P2** | ✅ **CLOSED_BY_PHASE_14R_8** |
| Independent review | ✅ No CRITICAL. 3 HIGH + 4 MEDIUM found, **all verified and fixed**; re-tested green |
| Clinician pilot | ▶️ **May continue.** Nothing it depends on regressed, and a fast reopen/recomplete now records correctly. |
| Clinician acceptance | ❌ `NOT_ASSESSED` — no clinician has tested this |
| Browser E2E (R1) | ❌ Still absent — no harness was created |
| Wide suite (R2) | ❌ Still incomplete at ~60% — pre-existing, not claimed green |
| Environment reconciliation (P3) | ❌ Still unvalidated at scale |
| Guarded pilot | 🚫 **Still blocked** — requires reconciliation review **and** clinician approval |
| Production rollout | 🚫 Not approved |
| **Phase 14.2 (billing posting)** | ▶️ **Unblocked from P2's side.** P2 was the stated blocker and is closed. Remaining prerequisites (billing policy approval, clinician acceptance) are unchanged and still apply. |

---

## 18. Remaining pilot risks

| # | Risk | Status |
| --- | --- | --- |
| ~~P2~~ | ~~Same-second recompletion loses a version~~ | ✅ **CLOSED** |
| P3 | Environment reconciliation unproven at scale on real data | Open — re-run per environment |
| P4 | Clinical usability unsigned | Open by definition |
| R1 | No browser E2E harness | Open — out of scope |
| R2 | Wide suite OOMs at 60% | Open — needs its own infrastructure fix |
| R4 | Order-set retargeting seeder never run here | Open — guard blocks at runtime; preflight reports it |
| **New** | Multi-process concurrency verified at the index/service level, not under real parallel load | Low — the DB guarantee is tested where it lives; observe during pilot |
| **New** | Occurrence ledger is per-environment: closing P2 in code does not close it in an unmigrated database | Low — preflight reports `BLOCKED` if missing; added as rollout step 0 |

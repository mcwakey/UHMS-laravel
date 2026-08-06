# O&G / Maternity — Snapshot Completion-Occurrence Identity (Design)

**Phase:** 14R.8
**Status:** Implemented
**Closes:** Risk **P2** — same-second reopen/recompletion collapse
**Scope:** completion identity only. No billing, no new clinical fields, no UI redesign.

---

## 1. Root cause

Snapshot identity was derived from the completion clock:

```php
// pre-14R.8
sprintf('route:%d@%s', $consultation->id, $completedAt->utc()->format('Y-m-d\TH:i:s\Z'))
```

`captureForCompletion()` used that string as its idempotency key against the unique index
`(consultation_route_id, completion_reference)`. The index did exactly what it was told: it
treated two rows with the same reference as the same thing.

The format is **second-granular**. So:

| Sequence | Reference computed | Result |
| --- | --- | --- |
| Complete at `10:00:00` | `route:7@2026-07-26T10:00:00Z` | v1 created |
| Reopen, edit, recomplete at `10:00:00` | `route:7@2026-07-26T10:00:00Z` — **identical** | existing row returned, **no v2** |

The second completion was a *genuine, clinically distinct* completion, and the clinical state at
that moment was never captured anywhere. v1 was never corrupted — this is a **record-completeness**
defect, not a corruption defect. That distinction matters for the rollback story (§9).

The trigger is realistic: reopen → fix one field → recomplete is a fast, common correction, and a
sub-second round trip on a local network is ordinary rather than exotic.

### Why the idempotency key was wrong in principle

The key conflated two different questions:

* *"Is this the same completion occurrence?"* — a **lifecycle** question.
* *"Did this happen at the same time?"* — a **clock** question.

They coincide often enough to look equivalent and diverge exactly when it hurts. Any clock-derived
identity has this bug at *some* precision; moving to milliseconds or microseconds would have
narrowed the window without closing it, and would have made the remaining failures rarer and
therefore harder to detect. Precision was never the fix.

---

## 2. Existing completion flow (as audited)

`ConsultationRouteService::completeRoute()`:

1. Early-returns if the route is already `COMPLETED` or `CANCELLED` — this is the **intended**
   idempotency and it is unrelated to P2.
2. Opens a transaction.
3. Writes `status`, `completed_by`, `completed_at`, `notes`.
4. Captured the maternity snapshot (14R.6), inside the same transaction.
5. Writes the route log and activity log.

### Candidate identity sources considered

| Candidate | Verdict | Why |
| --- | --- | --- |
| `visit_consultation_route_logs` | **Rejected** | Mutable, multi-purpose (start/pause/resume/complete), and written for reasons other than completion. Not a truthful 1:1 occurrence record. |
| `activity_log` (Spatie) | **Rejected** | Explicitly disallowed by spec, and correctly so: it is optional, can be async (`audit_streaming.async_writes`), and is suppressible. Identity must not depend on an observability concern. |
| `completed_at` at higher precision | **Rejected** | Narrows the window, never closes it. See §1. |
| `MAX(version) + 1` | **Rejected** | Unsafe without a lock, and it answers "how many snapshots exist", not "which completion is this". |
| Route status-history row | **Rejected** | No table records completion as its own immutable event. |
| **New occurrence ledger** | **Chosen** | Nothing existing truthfully represents exactly one completion occurrence. |

The audit conclusion was that the smallest honest fix is a dedicated ledger — the spec's fallback,
reached only after the preferred reuse options were ruled out on evidence rather than convenience.

---

## 3. Chosen identity

A **completion occurrence**: one row per genuine `→ COMPLETED` transition.

```
ACTIVE
  → occurrence 1 (ULID A) → COMPLETED   → snapshot v1  (occ:A)
  → REOPENED
  → occurrence 2 (ULID B) → COMPLETED   → snapshot v2  (occ:B)
```

Identity is a **ULID**, generated server-side at the moment the occurrence row is inserted:

* immutable and generated, never derived from a clock reading used for comparison;
* unique regardless of wall-clock collisions;
* lexicographically sortable, which keeps debugging humane without making sort order load-bearing.

`occurrence_number` (1, 2, 3…) is the human-facing counter and the concurrency guard. The ULID is
the identity; the number is the ordering.

`completed_at` is retained on the occurrence — as **information for humans and audit**, never as
identity. This is the whole design in one sentence.

---

## 4. Schema

### `consultation_completion_occurrences` (new)

| Column | Notes |
| --- | --- |
| `id` | PK |
| `consultation_route_id` | FK → `visit_consultation_routes`, cascade on delete |
| `visit_id` | FK → `visits`, null on delete |
| `occurrence_uid` | ULID (26 chars), unique — **the identity** |
| `occurrence_number` | unsigned int, 1-based per route |
| `previous_occurrence_id` | self-FK — explicit lineage chain |
| `from_status` / `to_status` | the transition actually recorded |
| `completed_at` | human/audit timestamp, **not** identity |
| `completed_by` | FK → `users`, nullable |
| `metadata` | JSON, nullable |
| `created_at` | **no `updated_at`** — append-only by schema |

Indexes, with explicit short names (MariaDB's 64-char limit makes generated names a real risk):

* `cco_uid_unique` — UNIQUE (`occurrence_uid`)
* `cco_route_number_unique` — UNIQUE (`consultation_route_id`, `occurrence_number`)

The composite unique is the **final concurrency guard**: two racing completions cannot both claim
occurrence *n*.

### `consultation_maternity_snapshots` (altered)

One nullable column: `completion_occurrence_id` (FK, after `previous_snapshot_id`).

Nullable is load-bearing, not laziness — see §6.

The existing unique index on `(consultation_route_id, completion_reference)` is **retained
unchanged**. It still enforces one snapshot per occurrence; only the meaning of the reference
changed.

---

## 5. Transaction boundary and idempotency

```php
DB::transaction(function () {
    $route->update([... 'status' => COMPLETED, 'completed_at' => now() ...]);

    $occurrence = $this->recordCompletionOccurrence($route, $user, $from); // lock + insert
    $this->captureMaternitySnapshot($route, $user, $occurrence);           // consumes it
    $this->log(...);
});
```

Ordering is deliberate:

* the occurrence is created **after** the status write, so an occurrence can only exist for a
  completion that really happened;
* the snapshot is captured **after** the occurrence, so it can never invent its own identity;
* everything shares one transaction, so a snapshot failure rolls back the completion rather than
  leaving a completed consultation with a half-written medico-legal record.

`completeRoute()` takes `lockForUpdate()` on the route row as the **first statement inside the
transaction**, and re-checks the status under that lock before doing anything else. The occurrence
allocation additionally reads the previous occurrence with its own `lockForUpdate()`.

Both of those are corrections made after independent review, and both matter:

* **Status must be re-checked under the lock.** The pre-transaction guard is only a cheap early
  exit. Without an in-transaction re-check, a double-submitted completion (two requests that both
  loaded the route while it was still `ACTIVE`) ran the completion write twice, minted a *second*
  occurrence and wrote a duplicate v2 with byte-identical clinical content — a ledger row asserting a
  completion that never happened. The old timestamp identity accidentally masked this by collapsing
  both writes; occurrence identity correctly refuses to, so the guard has to be explicit. This was a
  regression introduced by this phase and is now covered by a dedicated test.
* **The occurrence read must be a locking read.** Locking the *route* row is not enough. Under InnoDB
  REPEATABLE READ, a plain `SELECT` is served from the transaction's read view, established at the
  transaction's first consistent read. A caller that had already read something in the same
  transaction — `ConsultationNextPatientService::openNext` does — would miss occurrence #1, allocate
  #1 again, and on conflict re-read the *same stale view*, silently reintroducing P2 on the very path
  this phase exists to close.

**The occurrence is minted in exactly one place** — the completion transition. The snapshot service
*consumes* an occurrence and never mints one. That is what keeps a retried capture idempotent: a
retry passes the same occurrence, computes the same `occ:{ulid}` reference, and resolves the
existing row.

New idempotency key:

```
maternity-summary-snapshot + consultation route + completion occurrence + schema version
```

expressed on the row as `completion_reference = "occ:{ULID}"`.

### Failure matrix

| Failure point | Behaviour |
| --- | --- |
| Before occurrence insert | Whole transaction rolls back. No completion, no occurrence, no snapshot. No false success. |
| After occurrence, before snapshot | Both roll back together. If an occurrence is ever left without a snapshot (out-of-band recovery), capture can resume against **the same** occurrence and produce exactly one snapshot. |
| After snapshot insert, before response | Committed. A client retry recomputes the same reference and resolves the existing row. |
| Duplicate request after commit | Route is already `COMPLETED` → early return. No new occurrence. |
| Unique-key conflict | The winner is re-read under lock and must be **strictly newer** than the occurrence this call saw. A route-scoped re-read alone would be tautological — it can only return a row for this route — and would hand back the *previous* occurrence if the insert failed for another integrity reason, losing the completion silently. |
| Hash mismatch | `verifyPayloadHash()` fails closed. Tamper evidence is not bypassable through this path. |

---

## 6. Legacy compatibility

Pre-14R.8 snapshots keep their `route:{id}@{timestamp}` reference and have
`completion_occurrence_id = NULL`. They are **not backfilled**.

This is a truth requirement, not a shortcut. Fabricating occurrence rows for historical completions
would invent a clinical event record that never existed — in a medico-legal table, that is worse
than the original defect.

Compatibility surface:

```php
ConsultationCompletionOccurrence::isLegacyReference($ref); // not prefixed "occ:"
$snapshot->usesLegacyCompletionReference();                // null occurrence + legacy reference
$service->legacyCompletionReference($route);               // the exact old format, preserved
```

`completionReference($route, ?$occurrence)` returns `occ:{ulid}` when an occurrence is supplied and
falls back to the historical format when it is not, so old rows stay readable, comparable and
verifiable. Mixed history (legacy v1, occurrence-based v2) orders and renders normally — proven by
test check 23.

---

## 7. Hash compatibility

**Decision: the occurrence identity is envelope metadata, deliberately outside the hashed payload.**

The hash is computed over `ConsultationMaternitySummaryProjection::toCanonicalArray()` — the
clinical payload only. `completion_occurrence_id` is a column on the row, not a key in the payload.

Consequences:

* every existing hash remains **byte-identical** and continues to verify — no rehash, no migration,
  no schema-version bump;
* the hash keeps meaning exactly one thing: *this is the clinical content that was captured*.
  Mixing lineage bookkeeping into it would have muddied that.

The alternative — including the occurrence in a new hashed contract version — was rejected because
it would have created two hash contracts to reason about for no clinical benefit.

`SCHEMA_VERSION` therefore stays at `14R.6.1`. The clinical contract did not change.

As always, the hash is **tamper evidence**, not non-repudiation: anyone able to rewrite the row
could recompute it.

---

## 8. Append-only

Both tables use `ImmutableClinicalSnapshot`: no `updated_at`, no soft delete, and model-level
`updating` / `deleting` / `saving` guards that throw. There is no update route, no delete route and
no edit UI for either.

The new ledger inherits the same protections rather than defining weaker ones.

---

## 9. Query behaviour

| Path | Cost |
| --- | --- |
| Completion (flag off) | +1 read, +1 insert on the ledger. No maternity queries at all — verified. |
| Completion (flag on) | Same, plus the pre-existing 14R.6 snapshot capture. |
| Snapshot history | Unchanged: 1 query + 1 eager load, constant in version count. |
| Preview / summary / print | Unchanged. Memoised per request; a completed route serves the stored payload rather than re-deriving the projection. |

The ledger cost is paid on the completion write path only — never on read paths, and never in a
loop.

---

## 10. Rejected alternatives (summary)

* **Higher-precision timestamps** — narrows, never closes; makes residual failures rarer and harder
  to spot.
* **Random UUID per capture invocation** — would make every retry a new "occurrence", turning an
  idempotency bug into a duplication bug.
* **Version-count-based identity** — answers the wrong question and races.
* **Reusing the activity log** — optional, async-capable, suppressible.
* **Backfilling historical occurrences** — fabricates clinical history.
* **Client-generated identity** — moves a medico-legal invariant into JavaScript.

---

## 10a. Known coverage limit of the ledger

`EmergencySessionService` completes a consultation route directly via `forceFill()->save()` when a
case is disposed, bypassing `completeRoute()` entirely. Those routes get **no occurrence**, so
`currentFor()` returns `null` for them.

This is pre-existing behaviour and out of 14R.8's scope to change. It does not affect P2 — the
maternity snapshot is only ever captured inside `completeRoute()` — but the ledger is therefore
**not** a complete record of every `COMPLETED` route, and any future recovery path built on it must
handle `null` rather than assume otherwise. Documented here rather than quietly overclaimed.

---

## 11. What this phase does *not* change

Billing (still non-posting), feature flags (all still off), order sets, environment reconciliation,
clinical fields, snapshot UI, permissions, and the wide-suite memory situation. P2 was closed
without widening the blast radius.

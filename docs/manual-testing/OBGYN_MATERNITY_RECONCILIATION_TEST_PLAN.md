# O&G ↔ Maternity Reconciliation — Manual & Automated Test Plan

**Status:** Test design for future implementation phases (14R.2 → 14R.7).
**Nothing in this plan was executed in the audit phase (14R.1) — no runtime changes were made.**
**Companion:** `docs/maternity/OBGYN_MATERNITY_INTEGRATION_PLAN.md`

---

## 0. Preconditions

| Item | Requirement |
|---|---|
| Seeders | `ConsultationSpecialtySeeder`, `RoleSeeder`, maternity seeders applied |
| Profiles | Obstetrics + Gynaecology specialty profiles active |
| Users | Obstetrics doctor, Gynaecology doctor, maternity nurse, admin |
| Billing | `billing.maternity_billing.enabled=false` (**must remain false through 14R.5**) |
| Readiness | `CONSULTATION_OBSTETRIC_MATERNITY_READINESS_ENFORCED=false` |
| Baseline data check | Record the count of specialty entries in maternity-owned sections **per environment** before enabling any write-path change (was **0** in the audited dev environment) |

---

## 1. Automated tests (proposed)

Grouped by phase, mapping directly to the required scenarios in the phase brief.

### 1.1 Context resolver — 14R.2

| # | Test | Expected |
|---|---|---|
| T1 | Resolver returns `none` when nothing links | no context, no error |
| T2 | Resolver honours an explicit active link first | explicit link wins over visit/admission/profile |
| T3 | Resolver falls back visit → admission → single active profile, in order | documented precedence |
| T4 | **Multiple active pregnancy profiles** | returns `ambiguous`; **never auto-selects** |
| T5 | Consultation cannot silently create a pregnancy profile | no `PregnancyProfile` created by resolution |
| T6 | Positive `pregnancy_test` / obstetric diagnosis does not create or link a profile | no profile, no link |
| T7 | Link / relink / unlink writes an activity log row | audit present |
| T8 | Link survives consultation completion | link readable after completion, role `historical` |

### 1.2 Obstetrics workspace — 14R.3 ✅ IMPLEMENTED

> Automated coverage lives in `tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php` (22 passing) and `tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php` (10 passing, incl. the completed-consultation mutation matrix and query-count measurement). Manual scenarios S2/S3/S7 below remain required before enabling the write guard in any environment.

| # | Test | Expected |
|---|---|---|
| T9 | Obstetrics workspace loads with **no** maternity context | 200; no ribbon; sections editable (`RW-unlinked`) |
| T10 | Obstetrics workspace displays a linked pregnancy profile | ribbon shows GA, EDD, risk, latest ANC |
| T11 | **ANC recorded from Consultation creates exactly one `AntenatalVisit`** | `AntenatalVisit::count()` +1 |
| T12 | **ANC data is not duplicated into generic specialty entries** | no `antenatal_vitals` / `fetal_assessment` entry created by the ANC action |
| T13 | **Labor started from Consultation creates exactly one `LaborEpisode`** | `LaborEpisode::count()` +1 |
| T14 | Delivery / newborn / postnatal summaries render from source records | values match source rows; no consultation copies |
| T15 | Existing consultation completion still works | route completes as before |

### 1.3 Gynaecology — 14R.4 ✅ IMPLEMENTED

> Automated coverage: `ConsultationGynaecologyMaternityPhase14R4Test.php` (21), `ConsultationObgynOrderSetRetargetingPhase14R4Test.php` (10), `ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php` (10) and `ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php` (12). Run `php artisan consultation:obgyn-order-set-audit` before enabling any Gynaecology flag.

| # | Test | Expected |
|---|---|---|
| T16 | **Gynaecology does not force maternity context** | loads clean with no profile; no maternity panels |
| T17 | Gynaecology can explicitly link a pregnancy profile | small context card appears only after linking |
| T18 | Gynaecology is never auto-switched to Obstetrics | specialty profile unchanged after linking |
| T19 | Original Gynaecology session/history preserved after transition | session + entries intact |
| T20 | `menstrual_history.lmp` never auto-syncs to `pregnancy_profiles` | PP LMP unchanged unless explicitly adopted |

### 1.4 Summary, readiness, billing — 14R.6

| # | Test | Expected |
|---|---|---|
| T21 | Consultation summary includes the maternity projection when linked | section present, values from maternity records |
| T22 | Summary projection creates **no** specialty entries | entry count unchanged |
| T23 | Advisory readiness warns but never blocks (enforcement off) | warning shown; completion allowed |
| T24 | **Billing remains preview-only** | `postForSource` → `STATUS_POSTING_NOT_IMPLEMENTED` |
| T25 | **No duplicate billing source introduced** | one clinical action → at most one billing source |
| T26 | Reconciliation command dry-run makes no writes | zero row changes; report produced |

### 1.5 Cross-module regression — 14R.5 / 14R.7

| # | Test | Expected |
|---|---|---|
| T27 | Existing maternity pages still work | ANC/labor/delivery/newborn/postnatal screens 200 |
| T28 | Existing admission workflow still works | admission request/flow unchanged |
| T29 | Existing emergency workflow still works | emergency episode unaffected; no duplicate labor record |
| T30 | **EN/FR localisation parity** | recursive key parity for new bridge keys |

---

## 2. Manual test scenarios

### S1 — Obstetrics outpatient, no pregnancy context
1. Open an Obstetrics consultation for a patient with no pregnancy profile.
2. **Expect:** workspace loads; no maternity ribbon; obstetric sections editable; no prompt to create a profile.
3. Complete the consultation. **Expect:** completes normally.

### S2 — Obstetrics with linked pregnancy profile
1. Create a pregnancy profile in Maternity. Open an Obstetrics consultation for that patient.
2. **Expect:** ribbon shows profile, GA (source-labelled), EDD, risk, latest ANC, next ANC date.
3. **Expect:** obstetric history / LMP / EDD / GA render **read-only** with a visible "source: Maternity" affordance.

### S3 — Record ANC from Obstetrics
1. In the ANC panel, click **Record ANC Visit**; enter BP, weight, fundal height, FHR; save.
2. **Expect:** exactly one new `AntenatalVisit`; visible in the Maternity ANC list and ANC reports.
3. **Expect:** **no** `antenatal_vitals` / `fetal_assessment` specialty entry created.
4. **Expect:** ribbon "latest ANC" updates.

### S4 — Multiple active pregnancy profiles
1. Give a patient two active pregnancy profiles (data setup).
2. Open an Obstetrics consultation.
3. **Expect:** no automatic selection; an explicit selector with a clear warning; nothing is linked until the clinician chooses.

### S5 — Gynaecology, no pregnancy
1. Open a Gynaecology consultation.
2. **Expect:** menstrual/contraceptive/STI/pelvic/breast sections available; **no** maternity workflow, no ANC/labor panels, no pregnancy prompt.

### S6 — Gynaecology discovers pregnancy
1. In Gynaecology, record a positive pregnancy test.
2. **Expect:** **no** automatic profile creation and **no** specialty switch — only an offered action.
3. Click **Start/Link Pregnancy Workflow**; create/link a profile.
4. **Expect:** a small context card appears; the consultation remains Gynaecology; the original session and all entries are intact.

### S7 — Labor from consultation
1. With a linked profile, use **Start Labor Episode**.
2. **Expect:** exactly one `LaborEpisode`; labor panel shows episode + latest observation; the Maternity labor workspace shows the same single record.

### S8 — Admitted obstetric patient
1. Admit an obstetric patient; open an Obstetrics consultation.
2. **Expect:** ribbon shows admission ward/bed; admission owns bed/nursing/discharge; maternity owns pregnancy/labor; consultation owns the encounter; links to both visible.

### S9 — Emergency obstetric case
1. Create an emergency obstetric case; link maternity context; hand off to labor/admission.
2. **Expect:** emergency remains owner of the emergency episode; **no duplicate** emergency or labor record.

### S10 — Postnatal review
1. Open a postnatal-review consultation for a linked postnatal case.
2. **Expect:** postnatal readiness + latest observations shown read-only; the consultation note is encounter-level; recording mother/newborn observations navigates to postnatal records rather than duplicating them.

### S11 — Billing safety
1. Perform S3 (ANC from consultation).
2. **Expect:** billing stays **preview-only**; exactly one maternity source record; no second charge for the same act; consultation vs. maternity-event billing distinguishable; **no billing card appears in the doctor consultation workspace**.

### S12 — Historical reconciliation dry-run
1. Run `maternity:reconcile-obgyn-entries --dry-run`.
2. **Expect:** a classification report (safe to link / safe to migrate / conflict / historical-only / insufficient context); **zero** database writes; original specialty entries untouched.

---

## 3. Regression checklist (14R.7)

- [ ] Consultation workspace loads for **every** specialty profile (not just O&G)
- [ ] Consultation completion + readiness unchanged for non-O&G specialties
- [ ] Specialty entries save/edit/delete unchanged for non-maternity sections
- [ ] Maternity dashboard, ANC, labor, delivery, newborn, postnatal screens unchanged
- [ ] Admission request + discharge readiness unchanged
- [ ] Emergency workflow unchanged
- [ ] Investigations / prescriptions / procedures / tasks unchanged
- [ ] Consultation preview & print summary render with and without maternity context
- [ ] EN/FR parity for all new keys
- [ ] No new N+1 on the consultation workspace (ribbon must use the overview services, eager-loaded)

---

## 4. Commands permitted per phase

**Audit phase (14R.1) — what was actually allowed and run:**
```
git diff --check -- . ':!docs/prompt.md'
```
No full suite. No `composer test:wide`. No migrations. No seeders. No backfills.

**Implementation phases (14R.2+), targeted only:**
```
php artisan test tests/Feature/Consultations                       # specialty engine
php artisan test tests/Feature/MaternityFoundationPhase8Test.php   # maternity foundation
php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php
php artisan test --filter ConsultationMaternity                    # new bridge tests
php -l <changed files>
git diff --check -- . ':!docs/prompt.md'
```

`composer test:wide` is reserved for **14R.7** only.

---

## 5. Phase 14R.5 — operational handoffs

> Automated coverage: `ConsultationMaternityHandoffsPhase14R5Test.php` (18), `EmergencyMaternityHandoffsPhase14R5Test.php` (16), `AdmissionMaternityHandoffsPhase14R5Test.php` (15), `MaternityOperationalHandoffsPhase14R5Test.php` (15) — **64 tests**. Shared fixtures live in `tests/Feature/Concerns/BuildsMaternityHandoffFixtures.php`.

All four flags default **false**. Enable only the one under test.

| # | Scenario | Steps | Expected |
|---|---|---|---|
| **A** | Obstetrics outpatient | Link Pregnancy Profile → Record ANC → Create Admission Request (twice) | Exactly one open request, `source_type = consultation`, one maternity context link, one ANC record. **No admission, no invoice.** |
| **B** | Gynaecology, no pregnancy | Open a Gynaecology consultation, complete it | No context required, no handoff offered, completion unchanged |
| **C** | Gynaecology discovers pregnancy | Save a positive pregnancy test → explicitly create/link a profile → Refer to Obstetrics/Maternity (twice) | Specialty stays Gynaecology; original route and every entry intact; one target Obstetrics route linked to the same profile with `link_role = handoff`; no ANC/labor/request created |
| **D** | Emergency obstetric case | Link profile (mismatched patient first — must be blocked) → Start/reuse Labor (twice) → Create Admission Request (twice) | One Labor Episode, one open request with `source_type = emergency`, context links attached; bay and disposition history untouched |
| **E** | Maternity escalation | Set the Labor emergency-escalation flag → confirm nothing happened → perform the explicit handoff (twice) | Flag alone creates **no** Emergency case; explicit action creates exactly one through `EmergencyCaseService`; repeat reuses it; no admission request, no Theatre case |
| **F** | Admission conversion | Convert a maternity-aware request | Context propagates in the same transaction; one Admission link per context type; repeat is idempotent; a stage record already bound to another admission is **not** overwritten; bed/nursing/MAR/discharge unchanged |
| **G** | Postnatal review | Link a Consultation to a PostnatalCase → record an observation in Maternity → return | Read-only projection updates; no duplicate case; no duplicate observation; discharge readiness stays advisory |

**Return-context checks:** Consultation → Maternity → Consultation, Emergency → Labor → Emergency and Admission → Postnatal → Admission all return to the originating page and anchor. An external URL is rejected outright; an invalid context falls back to the target module's own show page.

**Flag-off checks:** with all four flags false, the Emergency and Admission maternity cards render nothing and issue **zero** queries, the Consultation handoff panel returns `enabled => false`, and every handoff endpoint returns 403.

---

## 6. Phase 14R.5.1 — handoff UI

> Automated coverage: `MaternityHandoffUiPhase14R5_1Test.php` (26), `MaternityHandoffModalIntegrityPhase14R5_1Test.php` (9), `MaternityHandoffSelectorsPhase14R5_1Test.php` (9) — **44 tests**.

The integrity suite is the standing guard: **any new handoff button without a dialog body fails the build.** Keep it that way.

| # | Scenario | Steps | Expected |
|---|---|---|---|
| **A** | Consultation → Admission Request | Open an active Obstetrics consultation with an explicit Pregnancy Profile → open the modal → submit → repeat | One request; the second attempt shows *Open Existing Admission Request* with its id and status; no admission, no bed reservation, no invoice |
| **B** | Emergency pregnancy context | Open an Emergency case with no link → open the selector → link → Start Labor → repeat → Create Admission Request → repeat | Profiles load **only** when the selector opens; one Labor Episode; one open request; repeats say "reused" |
| **C** | Admission context | Convert a maternity-aware request → open the Admission Maternity card → open the correction modal → relink with a reason | Propagated context is labelled separately from the operational origin; the old link survives as history; bed/nursing/MAR unchanged |
| **D** | Maternity → Emergency | Mark Labor/Postnatal escalation → confirm nothing was created → open the handoff dialog → confirm → repeat | The dialog states the flag created nothing; one Emergency Case; the repeat opens the same one; return lands back on the Maternity record |
| **E** | Gynaecology fallback | Remove the Obstetrics profile mapping → open a linked Gynaecology consultation | The reason is shown with a working link into standard consultation creation; the Gynaecology route and its entries are untouched |
| **F** | Permissions | Try each action with the bridge permission only, the target permission only, then both | Only with **both** does a form appear; one half alone renders nothing executable |

**Flag checks:** with all four flags false no trigger, no modal body and no candidate query exists. With context on and handoffs off the card renders but **no mutation form** appears. An external `return_route` is ignored and the action falls back to the target module's own page.

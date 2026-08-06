# O&G / Maternity Pilot — Clinician Guide

**For:** Obstetrics and Gynaecology clinicians taking part in the pilot
**Environment:** _________________  **Date:** _________________

---

## What this pilot is about

The system now keeps **one copy** of each piece of pregnancy information, in the module that owns it, and shows it wherever you need it.

| Module | Owns |
|---|---|
| **Consultation** | your encounter — complaints, examination, diagnoses, plan, orders, notes |
| **Emergency** | the acute episode — triage, bay, emergency treatment, disposition |
| **Admission** | the inpatient stay — ward, bed, nursing, medication, discharge |
| **Maternity** | the pregnancy record — Pregnancy Profile, ANC, Labor, Delivery, Newborn, Postnatal |

Two rules run through everything you will test:

1. **Nothing happens automatically.** The system never creates a pregnancy record, links a pregnancy, starts labour or raises an admission request on its own. You always press a button.
2. **A completed consultation keeps what was true at the time.** If someone corrects the pregnancy record next week, your completed note does **not** change. Today's truth is shown separately and clearly labelled.

Your job is to tell us whether that behaviour is **safe and usable**, not whether the code works — automated tests already cover the mechanics.

---

## How to find your pilot patients

Every pilot patient has a hospital number starting with:

```
MT-OBGYN-14R7-
```

followed by the scenario code, e.g. `MT-OBGYN-14R7-O2-XXXX`.

Your coordinator will give you the batch list with a direct link per scenario. **These are test patients. They are not real people. Do not use real patient data in this pilot.**

---

## Before you start

Tell your coordinator immediately, and stop, if you ever see:

- a pregnancy record you did not create
- a pregnancy linked to the wrong patient
- a completed consultation whose values changed after the fact
- any charge, invoice or billing action
- another patient's information

---

## Scenarios

For each one: follow the steps, then record the result at the end of this guide.

### A. Obstetrics with no pregnancy record — `O1`

**Do:** open the consultation and work normally.

**Expect:** everything behaves exactly as it does today. No pregnancy panel demands attention.

**Must not happen:** a Pregnancy Profile is created or linked on its own.

---

### B. Obstetrics with a linked Pregnancy Profile — `O2`

**Do:** open the consultation and look at the Maternity Context panel.

**Expect:** gestational age, EDD, dating method and the latest ANC visit, each labelled as coming from Maternity.

**Check:** does the panel tell you what you need at a glance, in the order you would want it?

---

### C. Record an ANC visit from the consultation — `O2`

**Do:** use **Record ANC** in the Maternity panel.

**Expect:** exactly **one** ANC visit is created, in Maternity. The consultation shows it; it does not store a second copy.

**Must not happen:** two ANC records; an ANC record you did not confirm.

---

### D. Two possible pregnancies — `O3`

**Do:** open the consultation.

**Expect:** the system says the context is **ambiguous** and asks you to choose. It does **not** pick one.

**Check:** is it obvious what you are being asked to decide?

---

### E. Gynaecology with no pregnancy — `G1`

**Do:** open and complete the consultation normally.

**Expect:** no maternity section at all. Completion is unchanged from today.

---

### F. Positive pregnancy test, nothing linked — `G2`

**Do:** open the consultation. A positive pregnancy test is already recorded.

**Expect:** an *offer* to start or link a pregnancy record — nothing more.

**Must not happen:** a Pregnancy Profile created because of the test result.

---

### G. Linking a pregnancy from Gynaecology — `G2` → link

**Do:** use **Start or Link Pregnancy Workflow** and link a profile.

**Expect:** a small pregnancy card appears, and the consultation states plainly: **"This consultation remains Gynaecology."**

**Must not happen:** the consultation turning into an Obstetrics consultation; ANC or labour buttons appearing.

---

### H. Adopting the LMP for dating — `G3` and `G4`

**Do:** on `G3`, use **Adopt LMP**. On `G4`, try the same.

**Expect:**
- `G3` — the saved LMP is adopted for pregnancy dating.
- `G4` — adoption is **refused**, because the pregnancy is dated by scan. The reason is shown instead of a dead button.

**Must not happen:** a scan-based due date being replaced by an LMP.

---

### I. Emergency pregnancy and labour handoff — `E1`, `E2`

**Do:** open the emergency case, link the pregnancy, then **Start/Open Labor**, then **Create Admission Request**. Repeat each action.

**Expect:** one labour episode, one admission request. Repeating says *"existing record reused"* and shows the record.

**Must not happen:** duplicates; labour starting because of a danger sign; Emergency losing ownership of the acute episode.

---

### J. Admission context — `A1`, `A2`

**Do:** open an admission converted from a maternity-aware request.

**Expect:** the pregnancy context is shown, and the origin (who raised the request) is shown **separately** from the clinical context. Bed, ward, nursing and discharge remain Admission's.

---

### K. Maternity → Emergency escalation — `M1`

**Do:** set the escalation flag on a labour episode. Check Emergency. Then use **Create Emergency Handoff**. Repeat it.

**Expect:** the flag alone creates **nothing**. The explicit action creates or opens **one** emergency case. Repeating reuses it.

---

### L. Postnatal review — `P1`

**Do:** link a postnatal case to a review consultation.

**Expect:** readiness and observation times shown read-only. Observations are still recorded in Maternity.

**Must not happen:** a duplicate observation; a second postnatal case.

---

### M. Live summary on an active consultation — `S1`

**Do:** open the summary tab and the summary preview.

**Expect:** **Current Maternity Record**, with pregnancy, ANC, labour, delivery, newborns and postnatal.

**Check:** is anything missing that you would want in a summary? Is anything there that should not be?

---

### N. Completion snapshot — `S2`

**Do:** complete the consultation, then reopen the summary.

**Expect:** **Maternity Context at Consultation Completion — Completion Snapshot v1**, with a "verified" integrity label.

---

### O. Current versus historical — `S2`

**Do:** after completion, have the pregnancy record changed. Reopen the completed summary. Then press **View Current Maternity Record**.

**Expect:** the completed summary still shows the **old** values. The current values appear only in a separate block marked *"Not part of the completion-time snapshot."*

**This is the single most important check in the pilot.** If the historical summary changes, stop and report it.

---

### P. Reopen and recomplete — `S3`

**Do:** reopen the consultation, change the pregnancy record, recomplete.

**Expect:** **v2** becomes the default; **v1** is still readable and unchanged.

**Also worth trying (Phase 14R.8):** do it **fast** — reopen, change one field and recomplete within
a second or two. You must still get a **v2**. Before 14R.8 a very fast correction could be recorded
as the same completion and the second version was silently not created. That is fixed; this step
confirms it in real use.

---

### Q. Completed consultation with no snapshot — `S4`

**Do:** open a consultation completed before snapshots were switched on.

**Expect:** a plain statement that no snapshot was captured, and that none has been invented.

**Must not happen:** today's values shown as if they were the completion-time values.

---

### R. Five-newborn print — `S5`

**Do:** print the completed summary.

**Expect:** the snapshot version, capture time and a historical label. All five babies readable.

**Check:** is the printed layout usable on paper?

---

### S. Billing de-duplication preview — `B1`

**Do:** open the administrative billing readiness view.

**Expect:** the maternity event owns the charge; the ordinary consultation attendance fee is treated **separately**.

**Must not happen:** any posting, any invoice, any charge button, any billing card inside your consultation workspace.

---

### T. Turning it off

**Do:** ask your coordinator to disable the pilot flags.

**Expect:** the panels and summary sections disappear; the system behaves exactly as before. Existing records are not deleted.

---

## Recording your results

Copy this table and fill it in. **Leave anything you did not test as NOT_RUN.**

| # | Scenario | Result (PASS / PASS_WITH_OBSERVATION / FAIL / BLOCKED / NOT_RUN) | Usability issue | Clinical-safety issue |
|---|---|---|---|---|
| A | Obstetrics, no context | NOT_RUN | | |
| B | Obstetrics, linked profile | NOT_RUN | | |
| C | Record ANC | NOT_RUN | | |
| D | Ambiguous profiles | NOT_RUN | | |
| E | Gynaecology, no pregnancy | NOT_RUN | | |
| F | Positive test, no transition | NOT_RUN | | |
| G | Explicit Gynaecology link | NOT_RUN | | |
| H | One-way LMP adoption | NOT_RUN | | |
| I | Emergency handoff | NOT_RUN | | |
| J | Admission propagation | NOT_RUN | | |
| K | Maternity → Emergency | NOT_RUN | | |
| L | Postnatal review | NOT_RUN | | |
| M | Live summary | NOT_RUN | | |
| N | Completion snapshot | NOT_RUN | | |
| O | Current vs historical | NOT_RUN | | |
| P | Reopen / recomplete | NOT_RUN | | |
| Q | No snapshot | NOT_RUN | | |
| R | Five-newborn print | NOT_RUN | | |
| S | Billing preview | NOT_RUN | | |
| T | Flag rollback | NOT_RUN | | |

---

## Sign-off

| Field | Entry |
|---|---|
| Reviewer name | |
| Role / specialty | |
| Environment | |
| Date | |
| Overall outcome | ☐ Accepted ☐ Rejected ☐ Needs changes |
| Signature / approved electronic acknowledgement | |

> **This section must be completed by a clinician.** It is not filled in by the development team, and no automated test can substitute for it.

---

## If something goes wrong

1. Stop using the affected scenario.
2. Note the patient number, the screen and the time.
3. Contact the pilot coordinator: ____________________
4. Rollback is immediate — the coordinator disables the feature flags and the system returns to its previous behaviour. Your existing records are not deleted.

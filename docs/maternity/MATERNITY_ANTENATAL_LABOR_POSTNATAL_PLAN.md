# Maternity, Antenatal, Labor, and Postnatal Plan

## Current State

Maternity is present as a department type and dashboard category. It participates in ward-style routing and journey worklists, and manual seed data includes a Maternity Ward. The system also has pregnancy-related complaint, ICD, emergency triage, and blood bank screening references.

No dedicated maternity, antenatal care, labor/labour, delivery, postnatal, or newborn workflow module was found in controllers, models, routes, migrations, views, or feature tests.

## Goal

Build maternity as a first-class clinical workflow on top of the strengthened admission and ward foundation. The module should support outpatient antenatal care, maternity admission, labor monitoring, delivery outcome capture, newborn records, postnatal care, billing, reports, and safe handoffs to theatre, emergency, ward, lab, pharmacy, and blood bank.

## Foundation Model

Recommended records:

- Pregnancy profile
- Antenatal visit
- Maternity admission
- Labor episode
- Partograph observation
- Delivery record
- Newborn record
- Postnatal observation
- Maternal discharge summary
- Neonatal discharge summary

Core links:

- patient_id
- visit_id
- admission_id
- department_id
- doctor_id
- midwife_id or nurse_id
- insurer and invoice context through visit billing

## Pregnancy Profile

Recommended fields:

- gravida
- para
- abortions
- living_children
- last_menstrual_period
- estimated_due_date
- gestational_age
- blood_group
- rhesus_status
- known_risks
- allergies
- previous_caesarean
- previous_pph
- hypertensive_disorder_risk
- diabetes_risk
- multiple_pregnancy
- hiv_status, if policy permits
- profile_status: active, delivered, closed, transferred

## Antenatal Care Workflow

Target flow:

1. Register or open pregnancy profile.
2. Record ANC visit.
3. Capture gestational age, blood pressure, weight, fundal height, fetal heart rate, presentation, danger signs, and risk flags.
4. Request investigations and ultrasound.
5. Record medication, supplements, immunisation, counselling, and next appointment.
6. Escalate high-risk pregnancy to consultation, emergency, admission, or maternity admission.

ANC dashboard should show:

- ANC visits today.
- High-risk pregnancies.
- Missed appointments.
- Expected delivery month.
- Pending lab/scan results.
- Referrals to admission or emergency.

## Labor and Delivery Workflow

Target flow:

1. Start labor episode from maternity admission, emergency, direct ward admission, or ANC referral.
2. Capture admission assessment and risk flags.
3. Start partograph monitoring.
4. Record contractions, fetal heart rate, cervical dilation, descent, membranes, maternal vitals, fluids, medication, and interventions.
5. Escalate to theatre or emergency if risk thresholds are crossed.
6. Capture delivery mode, outcome, placenta, blood loss, complications, maternal condition, and attending staff.
7. Create newborn record or records for multiple births.

Newborn record should capture:

- baby number for multiple births
- sex
- birth weight
- birth time
- APGAR scores
- resuscitation
- congenital concerns
- feeding status
- neonatal risk flags
- neonatal transfer destination, if needed

## Postnatal Workflow

Target flow:

1. Open postnatal care after delivery.
2. Track maternal vitals, bleeding, pain, uterine tone, wound condition, breastfeeding, counselling, medication, and danger signs.
3. Track newborn feeding, temperature, weight, jaundice, breathing, cord status, immunisation, and danger signs.
4. Record discharge readiness for mother and newborn separately.
5. Link follow-up appointment and referral if needed.

## Maternity Admission Integration

Maternity admission should use the same admission request and bed placement spine planned for inpatient care, with extra fields:

- pregnancy profile
- gestational age
- maternity reason: ANC complication, labor, postnatal, observation, emergency referral
- maternity package/service mapping
- newborn expected flag
- theatre escalation flag

## Billing and Service Mapping

Recommended mapping categories:

- ANC registration/package
- ANC follow-up
- maternity admission fee
- delivery normal
- assisted delivery
- caesarean/theatre handoff
- postnatal package
- newborn care
- ultrasound
- maternity consumables

Billing should reuse existing visit invoice mechanisms and show missing mapping warnings before posting charges.

## Dashboard and KPI Plan

Maternity dashboard should eventually show:

- ANC visits today.
- Active maternity admissions.
- Women in labor.
- Deliveries today.
- Caesarean referrals.
- High-risk pregnancies.
- Postnatal mothers.
- Newborns under observation.
- Pending investigations.
- Bed occupancy for maternity ward.

## Permission Plan

Recommended permissions:

- `maternity.view`
- `maternity.dashboard.view`
- `maternity.pregnancy.create`
- `maternity.pregnancy.update`
- `maternity.anc.record`
- `maternity.labor.start`
- `maternity.partograph.record`
- `maternity.delivery.record`
- `maternity.newborn.record`
- `maternity.postnatal.record`
- `maternity.discharge.clear`
- `maternity.reports.view`
- `maternity.settings.manage`

## Safe Implementation Phases

| Phase | Goal | Main Work |
| --- | --- | --- |
| 1 | Stabilise admission request and ward bed foundation | Complete admission parity phases first. |
| 2 | Add maternity foundation | Pregnancy profile model, routes, permissions, dashboard shell. |
| 3 | Add ANC | ANC visit forms, investigations, risk flags, follow-up dates. |
| 4 | Add maternity admission hooks | Link pregnancy profile to admission requests and maternity ward placement. |
| 5 | Add labor episode | Labor start, observations, partograph-ready data model. |
| 6 | Add delivery and newborn records | Delivery outcome, multiple births, APGAR, newborn identity links. |
| 7 | Add postnatal care | Mother/newborn observations and discharge readiness. |
| 8 | Add maternity billing mappings | Package/service mapping warnings and posting tests. |
| 9 | Add maternity reporting | ANC, labor, delivery, postnatal, newborn reports. |
| 10 | Add manual seed data and full feature tests | Safe demo patients and end-to-end scenarios. |

## Protection Rules

- Do not force every female patient into maternity.
- Do not require pregnancy fields for general admission.
- Do not change existing emergency triage pregnancy flags until maternity risk mapping exists.
- Keep theatre escalation as a handoff to the theatre/procedure workflow, not a duplicated theatre module.
- Keep newborn records linked to mother and visit/admission context, but avoid forcing newborn billing until billing rules are defined.

# Maternity Workflow Guide

This guide explains how the maternity workflow is intended to work across pregnancy profile, antenatal care, labor and delivery, newborn records, and postnatal care.

## 1. Overall Flow

The expected maternity journey is:

1. Create or identify the patient.
2. Create a pregnancy profile.
3. Record antenatal visits during pregnancy.
4. Start a labor episode when the mother presents in labor.
5. Record labor observations.
6. Record delivery details.
7. Create newborn records from the delivery.
8. Open postnatal care from the delivery.
9. Record mother and newborn postnatal observations.
10. Mark mother ready, newborn ready, then ready for discharge.
11. Use admission discharge readiness as an advisory check.

The workflow is intentionally linked end to end. Each later stage should carry context from the earlier stage, including patient, visit, admission, department, pregnancy profile, labor episode, and delivery record.

## 2. Pregnancy Profile

The pregnancy profile is the foundation record for the maternity journey.

Use it to capture:

- Mother patient
- Linked visit or admission
- Department
- Gravida, para, abortions, living children
- LMP and EDD
- Gestational age
- Blood group and rhesus status
- Known risks
- Previous caesarean
- Previous postpartum haemorrhage
- Hypertensive disorder risk
- Diabetes risk
- Multiple pregnancy

The profile is used by antenatal, labor, delivery, newborn, and postnatal workflows.

Expected use:

- Create one active pregnancy profile per current pregnancy.
- Update profile risk if new risk factors are identified.
- Close the profile only when the pregnancy journey is complete, transferred, or cancelled.

## 3. Antenatal Care

Antenatal visits are recorded under the pregnancy profile.

Use antenatal visits to capture:

- Visit date
- Gestational age
- Weight
- Blood pressure
- Pulse, temperature, respiratory rate
- Fundal height
- Fetal heart rate
- Fetal movement
- Presentation
- Urine protein and glucose
- Oedema
- Haemoglobin
- Danger signs
- Risk flags
- Assessment, plan, counselling
- Supplements and immunisations
- Next visit date
- Referral details where needed

How it should work:

- Each ANC visit belongs to the pregnancy profile.
- Danger signs and risk flags are advisory and should help clinicians notice risk.
- ANC referral or admission request should be an explicit user action.
- ANC does not automatically admit the patient unless a user creates an admission request.

Common signs the ANC workflow is working:

- Pregnancy profile page shows ANC summary.
- Maternity dashboard shows ANC visits today, missed visits, and profiles without ANC.
- ANC history shows recorded visits.

## 4. Labor And Delivery

Labor starts from a pregnancy profile or from an antenatal visit.

The labor episode tracks:

- Pregnancy profile
- Mother patient
- Visit/admission
- Department
- Labor onset
- Labor stage
- Membranes status
- Rupture of membranes time
- Liquor colour
- Presentation and fetal position
- Planned delivery mode
- Risk level
- Theatre or emergency escalation requirement
- Clinical summary and complications

Labor observations track:

- Observation time
- Maternal vitals
- Fetal heart rate
- Contractions
- Cervical dilation
- Descent
- Membranes/liquor
- Danger signs
- Risk flags
- Assessment and plan

How it should work:

- Start a labor episode when labor care begins.
- Record repeated labor observations during labor.
- Update labor stage as the patient progresses.
- Escalate to theatre/emergency only through explicit user actions.
- Record delivery from the labor episode.

## 5. Delivery Record

The delivery record is created from the labor episode.

It captures:

- Delivery date/time
- Delivery mode
- Delivery outcome
- Placenta status
- Estimated blood loss
- Maternal condition
- Complications
- Attending staff
- Newborn count
- Whether newborn records are pending
- Delivery status

How it should work:

- Delivery belongs to the labor episode and pregnancy profile.
- Delivery carries mother, visit, admission, and department context.
- Newborn records are created from the delivery record.
- Postnatal care is opened from the delivery record.

Important:

- Delivery does not automatically create newborn patient registrations.
- Delivery does not automatically post billing.

## 6. Newborn Records

Newborn records are created from the delivery.

Each newborn record captures:

- Birth order and baby number
- Sex
- Birth time
- Birth weight
- Length and head circumference
- APGAR scores
- Whether baby cried at birth
- Resuscitation requirement and details
- Congenital concerns
- Feeding status
- Temperature
- Breathing status
- Cord status
- Colour
- Risk flags
- Danger signs
- Neonatal condition
- Outcome
- Status
- Linked newborn patient, if explicitly created or linked

How it should work:

- Create one newborn record for each expected baby.
- Bulk create can create placeholders based on delivery newborn count.
- Complete newborn outcome/status so delivery pending flag can clear.
- Create or link a newborn patient only when needed and only by explicit action.
- Stillbirth records are supported and do not require newborn patient creation.

## 7. Postnatal Care

Postnatal care is opened from the delivery record.

The postnatal case links:

- Delivery record
- Labor episode
- Pregnancy profile
- Maternity case
- Mother patient
- Visit/admission
- Department
- Newborn records through the delivery

The postnatal case tracks:

- Case status
- Risk level
- Mother readiness
- Newborn readiness
- Ready for discharge
- Referral required
- Referral reason
- Follow-up date
- Follow-up instructions
- Notes

How it should work:

1. Open postnatal care from the delivery page.
2. Record mother observations.
3. Record newborn observations for live newborns.
4. Mark mother ready when clinically appropriate.
5. Mark newborn ready when clinically appropriate.
6. Mark ready for discharge when both mother and newborn are ready.
7. Mark referral required if danger signs or clinical concerns require escalation.
8. Add follow-up date and instructions where needed.

Stillbirth-only deliveries:

- Do not require newborn postnatal observation.
- The postnatal workflow should focus on mother care, follow-up, counselling, and documentation.

## 8. Mother Postnatal Observations

Mother observations capture:

- Observation time
- Blood pressure
- Pulse
- Temperature
- Respiratory rate
- Bleeding status
- Uterus condition
- Pain score
- Wound condition
- Breastfeeding status
- Mobility
- Urination
- Mental wellbeing note
- Mother danger signs
- Mother risk flags
- Assessment
- Plan
- Counselling

Danger signs and risk flags are advisory. They may update postnatal case risk/status, but they do not block recording the observation.

## 9. Newborn Postnatal Observations

Newborn observations capture:

- Observation time
- Temperature
- Weight
- Feeding status
- Breathing status
- Cord status
- Jaundice status
- Stooling
- Urination
- Activity
- Newborn danger signs
- Newborn risk flags
- Immunisation note
- Assessment
- Plan
- Counselling

Newborn observations are expected for live newborn records. Stillbirth records should not require newborn postnatal observation.

## 10. Discharge Readiness

Postnatal readiness is advisory by default.

The postnatal case can show:

- Mother ready
- Newborn ready
- Ready for discharge
- Referral required
- Follow-up date

Admission discharge readiness can show postnatal warnings if linked postnatal care exists.

Important:

- Postnatal readiness does not block admission discharge by default.
- Enforcement is controlled by config:

```env
ADMISSION_REQUIRE_POSTNATAL_READY_BEFORE_DISCHARGE=false
```

Only set this to true if the site wants postnatal readiness to become a hard discharge blocker.

## 11. Dashboard Indicators

The maternity dashboard should help staff see:

- Active pregnancies
- High-risk pregnancies
- Open maternity cases
- Maternity admissions
- Expected deliveries this month
- ANC visits today
- Missed ANC visits
- Active labor episodes
- Newborn records pending
- Newborns recorded today
- Active postnatal cases
- Mother observations today
- Newborn observations today
- Postnatal referrals required
- Postnatal follow-ups due this week
- Recent postnatal cases

## 12. What The Workflow Does Not Do

The current maternity workflow does not:

- Automatically create invoices
- Automatically post maternity package billing
- Automatically post newborn billing
- Dispense pharmacy items
- Consume stock
- Register official births with a civil registry
- Auto-create newborn patients unless a user explicitly chooses that action
- Auto-admit patients without an explicit admission request

## 13. Recommended Daily Use

For antenatal clinic:

1. Open or create pregnancy profile.
2. Record ANC visit.
3. Review danger signs and risk flags.
4. Record next visit or referral.

For labor ward:

1. Start labor episode from pregnancy profile.
2. Record labor observations.
3. Update labor stage.
4. Escalate if needed.
5. Record delivery.
6. Create newborn records.

For postnatal ward:

1. Open postnatal care from delivery.
2. Record mother observation.
3. Record newborn observations.
4. Add referral/follow-up if needed.
5. Mark mother ready.
6. Mark newborn ready.
7. Mark ready for discharge.
8. Review admission discharge readiness.

## 14. Troubleshooting

If postnatal care cannot be opened:

- Confirm the user has `maternity.postnatal.open`.
- Confirm a delivery record exists.
- Confirm the delivery is the correct one for the pregnancy.

If newborn observations cannot be recorded:

- Confirm postnatal care is open.
- Confirm the newborn belongs to the same delivery.
- Confirm the newborn outcome is not stillbirth.
- Confirm the user has `maternity.postnatal.newborn.record`.

If mother observations cannot be recorded:

- Confirm postnatal care is open.
- Confirm the user has `maternity.postnatal.mother.record`.

If discharge readiness still warns:

- Check mother ready status.
- Check newborn ready status.
- Check referral required flag.
- Check follow-up instructions.
- Check whether hard enforcement has been enabled in config.

## 15. Best-Practice Notes

- Use danger signs and risk flags to support clinical judgement, not replace it.
- Keep referrals explicit and documented.
- Record follow-up instructions before discharge when any postnatal concern exists.
- Do not close pregnancy/postnatal records too early if follow-up is still active.
- Keep newborn outcome and postnatal readiness up to date so dashboards remain accurate.

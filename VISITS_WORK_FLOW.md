You are a senior Laravel architect.

We are implementing the Visit Workflow Engine for a Hospital Management System (UHMS).

---

# 🎯 OBJECTIVE

Design a structured patient flow:

Visit → Triage → Consultation → Investigation / Referral

---

# 🧩 CORE RULES

## 1. VISIT CREATION

* When a visit is created:

  * status = TRIAGE
  * patient is sent to triage queue

---

## 2. TRIAGE MODULE

Create a triage system that:

* captures vitals:

  * temperature
  * blood_pressure
  * pulse
  * respiratory_rate
  * oxygen_saturation

* computes a triage_score:

  * ROUTINE
  * URGENT
  * EMERGENCY

---

## 3. TRIAGE OUTCOME

After saving triage:

* If EMERGENCY → status = EMERGENCY
* If severe → status = INPATIENT
* Otherwise:

  * status = WAITING_CONSULTATION

---

## 4. TRIAGE ACTION

Triage personnel must:

* select ONE consultation department

System must:

* assign visit.current_department_id
* create billing line for consultation
* move patient to that department queue

---

## 5. CONSULTATION PHASE

Doctor can:

* complete consultation
* refer to another consultation department
* send to investigation

---

## 6. REFERRAL RULES

* Cannot refer to same department
* Must track department history

---

## 7. INVESTIGATION FLOW

* Patient can be sent to lab, scan, x-ray
* status = WAITING_INVESTIGATION

---

## 8. VISIT STATUS ENUM

Define:

* TRIAGE
* WAITING_CONSULTATION
* IN_CONSULTATION
* REFERRED_CONSULTATION
* WAITING_INVESTIGATION
* IN_INVESTIGATION
* COMPLETED
* EMERGENCY
* INPATIENT

---

## 9. DATABASE DESIGN

Create:

### visits

* patient_id
* status
* triage_score
* current_department_id

### triages

* visit_id
* vitals
* triage_score

### visit_department_history

* visit_id
* department_id
* type
* status

---

## 10. SERVICES

Implement:

VisitService:

* createVisit()
* processTriage()
* assignDepartment()
* referPatient()
* sendToInvestigation()

---

## 11. BILLING INTEGRATION

* On triage department selection → create consultation billing line
* On referral → create additional consultation billing
* On investigation → create investigation billing

---

## 12. ARCHITECTURE RULES

* Use enums for status
* Use service layer for logic
* Keep controllers thin
* Use Eloquent relationships

---

# 🚀 GOAL

Build a structured, enforceable hospital workflow engine similar to real-world clinical systems.

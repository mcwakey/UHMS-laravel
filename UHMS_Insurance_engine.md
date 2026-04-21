You are a senior Laravel architect.

We are building an advanced Insurance Engine for a Hospital Management System (UHMS).

---

# 🎯 OBJECTIVE

Implement a real-time insurance validation and billing system with automatic fallback to cash when limits are exceeded.

---

# 🧩 DOMAIN MODEL

PatientInsurance:

* patient_id
* insurance_id
* valid_from
* valid_to

Limits:

* max_per_visit
* max_per_month
* max_per_year
* max_visits_per_month

---

# 📊 TRACKING

InsuranceUsage:

* patient_insurance_id
* visit_id
* amount_used
* created_at

---

# ⚙️ CORE SERVICE

Create InsuranceService with:

evaluateCoverage(patientInsurance, visit, incomingAmount)

---

# 🧠 LOGIC RULES

1. Validate date range
2. Check monthly visit count
3. Compute:

   * used_this_visit
   * used_this_month
   * used_this_year
4. Compute remaining limits
5. coverable = min(all remaining limits)

---

# 💰 COVERAGE RULES

Case 1: Full coverage
→ insurance pays all

Case 2: Partial coverage
→ split:
insurance pays coverable
patient pays remainder

Case 3: No coverage
→ full patient payment (cash)

---

# 🔄 AUTO FALLBACK

If insurance limits are exhausted:

* Mark visit insurance as EXHAUSTED
* All subsequent billing must use cash

---

# 🧾 BILLING DESIGN

billing_items:

* visit_id
* service_id
* insurance_amount
* patient_amount
* insurance_id (nullable)

---

# 🚫 RULE

Do NOT modify previous billing lines when insurance is exhausted.

---

# 📊 RESPONSE FORMAT

{
can_use: boolean,
covered_amount: number,
patient_amount: number,
reason: string|null,
remaining_limits: {...}
}

---

# 🧱 IMPLEMENTATION REQUIRED

Generate:

* migrations
* models
* InsuranceService
* BillingService integration
* example usage in VisitService

---

# 🚀 GOAL

Build a production-grade insurance engine with real-time constraint enforcement and automatic fallback.

Wait for next instruction.

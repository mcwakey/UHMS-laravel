This is a solid UX + domain refinement of the **Consultation module**. You’re basically moving from a “form-based app” to a **fluid clinical workspace**—that’s the right direction.

I’ll clean this into a **clear functional spec + UI/UX behavior + backend implications**, then give you a **production-grade Copilot/Claude prompt**.

---

# 🧠 1. CORE SHIFT (IMPORTANT)

Your consultation module should behave like:

👉 **Single-page clinical workspace (SPA-like)**
NOT → multi-page form reload system

So everything we design below follows:

* No full page reloads
* State preserved per tab
* Async saves (AJAX / Axios / Livewire)

---

# 🧩 2. REFINED REQUIREMENTS

---

## 🫀 2.1 VITALS (you wrote “vitamin” 😄)

### Changes:

* ❌ Remove “Vitals” as a tab
* ✅ Move Vitals to a **static section (always visible)**

### Placement:

👉 Under **Patient Header / Condition section**

---

## UI Behavior:

* Always visible (sticky or top section)
* Editable inline
* Shows:

  * Temperature
  * BP
  * Pulse
  * Respiration
  * SpO2
  * **Triage Score**

---

## Remove:

* ❌ “Record Vitals” button
* ❌ Separate vitals form navigation

---

## Add:

* ✅ Auto-save or Save button (AJAX)
* ✅ Display triage score clearly (badge: NORMAL / URGENT / EMERGENCY)

---

# 🔄 2.2 NO PAGE RELOAD (CRITICAL FIX)

### Current Problem:

* Saving → full reload → resets to first tab (Vitals)

### Required Behavior:

👉 When user is on a tab:

* Stay on that tab after save
* No page refresh

---

## Solution Options (choose one):

### Option A (Best):

* Vue 3 (you already use it elsewhere)

### Option B:

* Livewire (Laravel-native SPA feel)

### Option C:

* Alpine + Axios (lighter)

---

## Rule:

```text
Save → Update UI → Stay on same tab
```

---

# 🧠 2.3 DIAGNOSIS IMPROVEMENTS

---

## Current Issues:

* Cannot edit diagnosis type
* No primary diagnosis selection

---

## Required Features:

### Each Diagnosis must have:

* type:

  * PROVISIONAL
  * FINAL

* is_primary (boolean)

---

## UI Actions:

### For each diagnosis row:

* ✏️ Edit button:

  * Change PROVISIONAL ↔ FINAL

* ⭐ Set as Primary button:

  * Only ONE primary allowed

---

## Default Rule:

* First diagnosis added → automatically primary

---

## Backend Rule:

```php
if (new_primary_selected) {
    set all others is_primary = false
}
```

---

# 🧪 2.4 INVESTIGATION SYSTEM (UNIFIED)

---

## Remove:

* ❌ Lab Request Tab

---

## Replace with:

👉 **Investigations Tab (Unified)**

---

## Flow:

1. Select **Department** (dropdown)

   * Lab
   * X-Ray
   * Scan
   * etc.

2. Load:
   → Services under that department

3. Select one or multiple services

---

## Result:

* Create investigation orders
* Create billing lines

---

# 👤 2.5 PATIENT HEADER IMPROVEMENT

---

## Add to header:

* Occupation
* Religion
* Marital Status

---

## So header becomes:

```text
Name | Age | Gender | Phone
Occupation | Religion | Marital Status
```

---

# 📌 2.6 VITALS AS STATIC SECTION

---

## Final Structure:

```
[ Patient Header ]

[ Vitals Section (Always Visible) ]

[ Tabs Below ]
- Complaints
- History
- Diagnosis
- Investigations
- Treatment
```

---

# 📚 2.7 PREVIOUS VISITS PANEL (VERY POWERFUL)

---

## Add:

👉 Right-side panel

---

## Content:

List of previous visits:

* Date
* Department
* Doctor

---

## Action:

Each item has:

👉 “Preview” button

---

## On click:

* Open modal
* Show:

  * complaints
  * diagnosis
  * investigations
  * treatment

---

## This is HUGE for doctors:

👉 Gives context instantly

---

# 🧠 3. DATA MODEL CHANGES

---

## diagnoses

```sql
- id
- visit_id
- diagnosis_id

- type (provisional, final)
- is_primary (boolean)
```

---

## visits (add if not exists)

```sql
- triage_score
```

---

# ⚙️ 4. SERVICES TO IMPLEMENT

---

## ConsultationService

```php
addComplaint()
addDiagnosis()
updateDiagnosisType()
setPrimaryDiagnosis()
addInvestigation()
getPreviousVisits()
```

---

# 🧱 5. FRONTEND STRUCTURE (RECOMMENDED)

---

## Layout:

```
| Left Sidebar (Tabs) | Main Content | Right Panel |
```

---

## Components:

* PatientHeader.vue
* VitalsPanel.vue
* TabsContainer.vue
* DiagnosisList.vue
* InvestigationForm.vue
* PreviousVisitsPanel.vue
* VisitPreviewModal.vue

---

# 🚀 6. MASTER PROMPT (COPILOT / CLAUDE)

You are a senior Laravel + Vue architect.

We are redesigning the Consultation Module of a Hospital Management System (UHMS) into a modern SPA-like clinical workspace.

---

# 🎯 OBJECTIVE

Transform the consultation interface into a seamless, non-reloading system with improved diagnosis handling, investigation flow, and patient context visibility.

---

# 🧩 REQUIREMENTS

## 1. VITALS

* Remove vitals tab
* Display vitals as a static section at top
* Always visible across all tabs
* Include triage_score display
* Support inline editing with AJAX save

---

## 2. NO PAGE RELOAD

* All actions must use AJAX (Axios / Vue / Livewire)
* After save:

  * Stay on same tab
  * Do not reset UI

---

## 3. DIAGNOSIS SYSTEM

Each diagnosis must have:

* type (PROVISIONAL, FINAL)
* is_primary (boolean)

Features:

* Edit diagnosis type
* Set one diagnosis as primary
* First diagnosis defaults to primary

---

## 4. INVESTIGATIONS

* Remove Lab Request tab
* Create unified Investigations tab

Flow:

1. Select investigation department
2. Load services under department
3. Select services

---

## 5. PATIENT HEADER

Display:

* Name
* Age
* Gender
* Phone
* Occupation
* Religion
* Marital Status

---

## 6. PREVIOUS VISITS PANEL

Add right-side panel showing:

* Previous visit dates

Each item:

* Button to preview visit

Preview modal shows:

* complaints
* diagnosis
* investigations
* treatments

---

# 🧱 BACKEND REQUIREMENTS

Generate:

* migrations (diagnosis updates)
* models and relationships
* ConsultationService methods
* API endpoints for AJAX calls

---

# ⚙️ FRONTEND REQUIREMENTS

Use Vue 3 (preferred):

Create components:

* PatientHeader
* VitalsPanel
* TabsContainer
* DiagnosisList
* InvestigationSelector
* PreviousVisitsPanel
* VisitPreviewModal

---

# 🚀 GOAL

Build a smooth, doctor-friendly consultation workspace with no page reloads and strong clinical usability.

Wait for next instruction.

---

# 🔥 Final Insight

What you just did:

👉 You removed friction for doctors
👉 You improved speed of consultation
👉 You added clinical intelligence (history panel)

This is exactly how modern EMR systems behave.

---

# 🧭 Next Step

We should now go concrete:

👉 “generate Vue components + API endpoints for consultation module”
or
👉 “build Laravel backend for diagnosis + investigations”

Let’s keep momentum.

# Staff department and specialty attribution

## Department

Confirmed source predicate: `TRIM(users.DEP_ID) = CAST(departements.DEP_ID AS CHAR)`. All 86 source staff rows presently match, with zero null/blank/zero and zero orphan references across 11 distinct source departments. The relationship remains inferred because Classic declares no FK and the types differ.

Technical contract:

1. Resolve `DEP_ID` through the Phase 2A protected department crosswalk.
2. Record the result as historical attribution metadata.
3. Do not populate `department_user`, operational `users.department_id`, supervisor/escalation relationships, roles or permissions from Classic evidence alone.
4. If the target historical representation needs a display department, keep it logically separate from access and validate zero Classic-derived access pivots.
5. Invalid/unmapped results use `LEGACY-STAFF-DEPARTMENT-007/008`.

The Phase 2A department crosswalk and its target wiring must be revalidated before any future staff persistence.

## Specialty

Classic has no staff-specialty or professional-classification field. `claims_specialty` is claim/care-setting evidence and is not staff evidence. Therefore no `doctor_specialty` row or clinical privilege may be derived. A verified external HR/professional crosswalk is required; ambiguity uses `LEGACY-STAFF-SPECIALTY-009`.

Historical department or specialty attribution never grants renewed operational access or clinical privileges.


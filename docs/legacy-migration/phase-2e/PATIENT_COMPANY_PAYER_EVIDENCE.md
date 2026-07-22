# Patient Company payer evidence

**Confirmed:** 16,950 patients comprise 11,652 blank and 5,298 nonblank Company values (1,775 distinct nonblank). Of nonblank patient Company rows, 1,193 agree with at least one source insurance Company, 4,104 conflict with all insurance rows for that patient, and one has no insurance row. Source name/short-name comparison yields 1,232 single Classic catalogue candidates and 4,066 without a candidate; neither is a target-provider mapping.

`patients.Company` is corroborating evidence only. Blank is no evidence, never cash. An exact `INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1` result may strengthen an already compatible insurance row only after a unique approved Phase 2A source-row-to-target-provider crosswalk resolves; it cannot create a membership. Conflicts preserve both facts and block automatic consolidation; no preference is inferred.

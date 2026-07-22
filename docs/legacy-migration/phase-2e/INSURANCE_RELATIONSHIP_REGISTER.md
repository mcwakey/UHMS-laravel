# Insurance relationship register

| ID | Source → target | Exact predicate | Failure behavior |
|---|---|---|---|
| INS-REL-001 | insurance.PAT_ID → Phase 2C patient | equality then protected crosswalk | orphan/root hold |
| INS-REL-002 | insurance.Company → Phase 2A provider | `INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1`: exact canonical `PrivateName`, compatible type, then unique approved Phase 2A source-row-to-target-provider crosswalk | withhold group |
| INS-REL-003 | InsType → type crosswalk | exact normalized controlled value | type exception |
| INS-REL-004 | Scheme → protected history | no target relationship | history only |
| INS-REL-005 | source row → patient/provider group | resolved patient token + provider token | withhold |
| INS-REL-006 | patients.Company → corroboration | same coordinated PAT_ID plus agreement with the resolved INS-REL-002 provider outcome | conflict evidence |
| INS-REL-007 | patients.BillStatus → payer class | exact crosswalk | exception |
| INS-REL-008 | patient quarantine → membership hold | unchanged `PATIENT-PRIV-009` / `legacy-insurance-chain-v1` root | hold chain |
| INS-REL-009 | orphan reference → orphan chain | unchanged `PATIENT-PRIV-009` root plus secondary Phase 2E source-row token | hold chain |
| INS-REL-010 | selected representation → history | exact group + rule-version linkage | provenance failure |

Normative records define sentinel, exception, extraction and privacy links. No relationship creates an artificial parent.

# Patient insurance column mapping

| Source | Target / disposition | Rule | Basis |
|---|---|---|---|
| insurance.INS_ID | protected crosswalk/history | never target PK | approved safety |
| insurance.PAT_ID | patient_id | Phase 2C exact protected map only | approved policy |
| insurance.InsType | provider/type compatibility | explicit crosswalk | D-205 |
| insurance.Company | insurance_provider_id candidate | INSURANCE-COMPANY-TO-PROVIDER-PROJECTION-V1 plus a unique approved Phase 2A source-row-to-target-provider crosswalk; source name/short candidate alone is unresolved | approved dependency/specification |
| insurance.MemberNo | membership_number | preserve evidenced text; blank to null | target nullable |
| insurance.IssueDate | start_date | valid date only; zero to unknown/null | D-206 |
| insurance.ExpiryDate | expiry_date | valid date only; zero to unknown/null | D-206 |
| insurance.Scheme | protected history only | no target field; no tier inference | confirmed target |
| insurance.Plan | protected history only | no target field; no policy inference | confirmed target |
| patients.Company | corroboration only | cannot create membership | technical specification |
| patients.BillStatus | payer class evidence | explicit crosswalk; cannot create membership | D-205 |

The normative rules are in `insurance_column_mappings.json`. No field uses a convenient default.

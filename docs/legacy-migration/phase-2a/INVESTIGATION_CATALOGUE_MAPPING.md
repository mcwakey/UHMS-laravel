# Investigation Catalogue Mapping

## Structure

[Technical specification] `services → serv_group → serv_criterias → serv_options_cri` maps conceptually to service catalogue → investigation headers → investigation criteria/options. `serv_options_out` remains service result-option evidence until its semantics are approved.

| Table | Rows | Key evidence | Disposition |
|---|---:|---|---|
| `serv_group` | 155 | 146 service matches, 9 orphans; 90 blank names | headers after service mapping |
| `serv_criterias` | 200 | 193 group matches, 7 zero sentinels; 12 blank criteria | criteria after header mapping |
| `serv_options_cri` | 20 | no key; IDs all zero; all 20 criterion joins orphan | quarantine all |
| `serv_options_out` | 32 | all service joins match; 20 blank options | evidence/deferred |

Criterion fields map as follows: Criteria→name, Unit→approved unit, Sort→sort_order, NormalValue/MaxNormal→approved range grammar, ResultType→input_type. All 200 ResultType values are blank; the target `text` default is prohibited. Blank or literal zero normal bounds are not clinical reference ranges by default.

Classic header names allow 200 characters and criterion names 250, while installed target names allow 191. Overlength values quarantine; they are not truncated.

Keyless options use canonical multiset hashing and preserve multiplicity. Natural keys are parent crosswalk plus normalized name/option, never zero option IDs or row position.

No criterion option can proceed while its parent relationship remains entirely orphaned. `InvestigationServiceCriteriaSeeder` and `LabCatalogSeeder` are target baseline candidates only; neither is a source crosswalk.

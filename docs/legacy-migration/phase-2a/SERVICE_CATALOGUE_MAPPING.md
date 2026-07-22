# Service Catalogue Mapping

[Confirmed] `services` has 203 rows and no timestamp. Natural keys are unsafe: normalized name has 7 duplicate groups/16 rows; department+name has 14 incomplete rows and 5 duplicate groups/10 rows.

| Source | Target/disposition |
|---|---|
| `SERV_ID` | protected crosswalk identity |
| `Service` | normalized `service_catalog.name`; blank/duplicate quarantine |
| `Departement` | department-name inferred join then approved department crosswalk; 14 blank, one nonblank orphan |
| `Amount` | candidate base `service_catalog.price`, decimal scale 2; 21 zero is a value, not a sentinel |
| `Nhis`, `Private` | provider/type-bound `service_prices` only after price semantics and target payer binding |
| `Protocol`, `PrivateNhis` | deferred; observed all zero and semantics unapproved |
| `Corp` | evidence only (FALSE 198, TRUE 5); no target catalogue field |
| `ResultType` | quarantine until explicit mapping; all 203 blank, never target default `free_text` |

Target service code is unique but Classic has none. [Technical specification] Future codes must be controlled configuration values, not lossy slug identities. The target unique price tuple includes nullable provider; because MySQL permits repeated NULLs, preflight/application validation must prevent duplicate type-default prices.

Target `code` and `category` are required and have no evidenced Classic columns. They are blocking configuration/crosswalk outputs, not schema defaults. Target `overall_result_type='free_text'` is likewise prohibited while every Classic result type is blank.

Reconcile all 203 rows and each price field independently. Do not run `ServiceCatalogSeeder` or `InsurancePricingSeeder` as migration matchers.

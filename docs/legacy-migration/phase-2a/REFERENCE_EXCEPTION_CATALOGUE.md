# Reference Exception Catalogue

Machine contract: [reference_exception_codes.json](specifications/reference_exception_codes.json).

Codes follow `LEGACY-REF-<DOMAIN>-<NUMBER>`. Every exception records the protected source identity, rule version, severity, blocking flag, disposition, owner, timestamps and resolution evidence. Reports contain counts/hashes, not identifying or secret values.

Every machine definition also specifies domain/category, trigger condition, source scope, retryability, manual-review requirement, owner role/SLA, release condition, reconciliation treatment, blocking scope and privacy classification. No code is releasable on description alone.

## Domains

| Domain | Principal blocking conditions |
|---|---|
| Common | invalid source identity, unmapped value, orphan relation, ambiguous target |
| Organisation | setting conflict, invalid contact |
| Department/specialty | blank/duplicate keys, type unmapped, non-specialty claim classifications |
| Insurance | provider duplicates, ambiguous targets, unmapped type |
| Service/investigation | duplicate keys, unresolved departments/parents, blank result type, orphan options |
| Clinical reference | blank/duplicate complaint, ICD conflict, procedure ambiguity, unusable dormant relations |
| Product | blank name/type/unit/category, duplicate code, unresolved department/product reference |
| Supplier | blank/ambiguous supplier, orphan batch supplier |
| Ward/bed | missing ward, invalid ward/bed key, rate semantics, operational state deferred |
| Finance | blank/duplicate category, type unmapped, incomplete bank configuration, zero bank sentinel |

## Processing rules

Blocking exceptions quarantine the affected row and dependency chain. They do not authorize defaulting, stubs, fuzzy merges or parent synthesis. Multiple secondary reason codes may accompany one mutually exclusive primary row disposition. Resolution requires the catalogue's owner and exit criteria; automatic resolution is prohibited.

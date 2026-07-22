# Reference Mapping Readiness Matrix

“Ready” means ready to refine/approve a column-level mapping contract. It does not authorize importer implementation.

| Domain | Source evidence | Target evidence | Column dispositions | Natural key | Values | Relationships | Exceptions/reconciliation/extraction | Ready | Blocker |
|---|---|---|---|---|---|---|---|---|---|
| Organisation settings | Complete | Complete | Complete | Config key | Partial | n/a | Complete | Yes, configuration review | invalid phone; canonical setting keys |
| Facilities | No source master | No target entity | Complete boundary | None | n/a | n/a | Complete | No | architecture has no facility entity |
| Departments | Complete | Complete | Complete | Candidate | Pending | Complete | Complete | No | explicit type/result/stock flags and code crosswalk |
| Specialties | Complete | Complete | Complete | Explicit crosswalk | Partial | Complete | Complete | No | claim classifications mixed with specialties |
| Insurance providers | Complete | Complete | Complete | Candidate | Partial | Complete | Complete | No | five duplicate groups; target-existing match |
| Services | Complete | Complete | Complete | Candidate, duplicated | Partial | Complete | Complete | No | duplicate keys, department gaps, price/result semantics |
| Investigations | Complete | Complete | Complete | Candidate, duplicated | Pending | Complete | Complete | No | 20 criterion options orphan; blank result types |
| Clinical catalogues | Complete | Complete | Complete | Candidate, duplicated | Partial | Complete | Complete | No | heavy complaint/ICD duplication; dormant relations unusable |
| Wards/beds | Complete for beds; no ward master | Complete | Complete | Configured ward + bed | Pending | Complete | Complete | No | all 18 lack ward assignment; state/rate deferred |
| Products/drugs | Complete | Complete | Complete | Candidate | Pending | Complete | Complete | No | missing name/unit/category/type; price semantics |
| Suppliers | Complete | Complete | Complete | Review-only | n/a | Complete | Complete | Yes, target review | no name-only auto-merge/contact overwrite |
| Stock locations | No source master | Complete | Complete boundary | Configured target ID | Pending | Complete | Complete | No | exact Main Store/Pharmacy IDs and uniqueness |
| Finance references | Complete | Complete | Complete | Candidate | Partial | Complete | Complete | No | duplicate categories; bank GL/currency; no transaction mapping |

No domain is ready for importer implementation. Seeder execution is not an approved shortcut.

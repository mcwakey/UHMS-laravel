# Patient marital-status mapping

Target application enum values are exactly `single`, `married`, `divorced`, and `widowed`; the database does not enforce them. The captured source partition is:

| Source safe category | Count | Target | Mapping state |
|---|---:|---|---|
| `SINGLE` | 8,240 | `single` | exact semantic match |
| `MARRIED` | 6,976 | `married` | exact semantic match |
| `WIDOW(ER)` | 353 | `widowed` | explicit semantic normalization |
| blank | 1,381 | null | nullable absence |
| other nonblank | 0 | withheld if later observed | out-of-domain |

Separated, cohabiting, child/minor-like, misspelled, combined or unknown values have no approved target member and are withheld at field level. `divorced` is target-supported but was not observed in the source allow-list. A nonblank unrecognized value is never silently null/default. Optional field failure does not block the patient when safe omission is possible. Existing-target status remains immutable.


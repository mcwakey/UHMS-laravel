# Patient religion mapping

Target religion is nullable free text, not a database/application enum. The captured source partition is complete and default-free:

| Source safe category | Count | Target representation | State |
|---|---:|---|---|
| `CHRISTIANITY` | 12,438 | `Christianity` | normalized display |
| `MUSLIM` | 2,749 | `Muslim` | normalized display |
| `OTHER` | 112 | `Other` | explicit same semantic label, not fallback |
| blank | 1,651 | null | absence provenance |
| unrecognized nonblank | 0 | withheld if later observed | drift/exception |

This allow-list is versioned in `religion_value_crosswalks.json`. Validity requires deliberate decoding, NFC, no controls and max 100. Any future nonblank value outside the list is withheld; it is not null and not `Other`. Religion cannot be inferred from names, address, department or contact information and cannot identify/match a patient. Existing-target values are immutable.


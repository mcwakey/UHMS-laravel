# Staff column mapping

The authoritative 11-row contract is [staff_column_mappings.json](specifications/staff_column_mappings.json). Every Classic `users` column has exactly one primary disposition.

| Source | Disposition | Target/specification consequence | Blocking condition |
|---|---|---|---|
| `USER_ID` | Crosswalk | Protected source key and extraction identity; never renewed PK | Missing/duplicate extraction key |
| `DEP_ID` | Crosswalk | Historical attribution through the approved Phase 2A department crosswalk | Invalid/unmapped department |
| `FullName` | Transform | Protected decode/NFC/display evidence; never identity proof; no automatic first/last split | Display components/representation unresolved |
| `UserName` | Protected provenance | Diagnostic fingerprint only; never target credential or key | Duplicate/collision is review evidence |
| `RegDate` | Evidence-only | Protected historical timestamp, not watermark proof | Invalid timestamp or snapshot drift |
| `Password` | Security-excluded | Zero values exported or mapped | Any leakage stops the run |
| `CanAdd`, `CanEdit`, `CanDelete`, `CanPrint` | Security-excluded | Zero values exported; zero roles/permissions derived | Any derived authorization stops the run |
| `Status` | Evidence-only | Preserve ACTIVE/INACTIVE evidence; historical-only candidate is always disabled | Unknown category or access derivation |

Classic `FullName` may be decoded from its source character set, normalized to Unicode NFC, trimmed and comparison whitespace collapsed. Meaningful punctuation is retained. Display transformation is never an identity key, and first/last-name decomposition requires protected HR input or the approved Phase 3 representation.

## Status crosswalk

| Source class | Observed | Historical-only result | Existing renewed user |
|---|---:|---|---|
| `ACTIVE` | 85 | Disabled/non-login candidate only | Preserve target status |
| `INACTIVE` | 1 | Disabled/non-login candidate only | Preserve target status |
| Blank/null | 0 | Quarantine; no default | Preserve target status |
| Unknown/out-of-domain | 0 | Quarantine; no default | Preserve target status |
| Obsolete/deleted/suspended-like | 0 | No mapping inferred; quarantine if introduced | Preserve target status |

No Classic status grants authentication, authorization, role, permission, clinical privilege or department access.

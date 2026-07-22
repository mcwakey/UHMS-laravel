# Insurance Provider Mapping

[Confirmed] `sett_private` has 38 rows but only 33 normalized `(PrivateName, PrivateType)` keys; five duplicate groups cover ten rows. Its catalogue is incomplete relative to insurance usage (4,017 nonblank company values are unmatched).

| Source | Disposition | Target |
|---|---|---|
| `PINS_ID` | Crosswalk | protected identity |
| `PrivateName` | Transform | `insurance_providers.name` |
| `PrivateShort` | Transform | `short_name`; all normalized values equal full name in this snapshot |
| `PrivateType` | Crosswalk | CORPORATE→CORPORATE/corporate; PRIVATE INSURANCE→PRIVATE/private |

Natural key is Unicode-normalized, trimmed, whitespace-collapsed, casefolded name plus mapped type. No fuzzy or punctuation-insensitive match. Target has no unique name/code constraint, and its database default provider type is unsafe; the mapped type must be explicit.

Match order: pre-approved mapping, unique exact code, unique normalized name+compatible type, then unique short name+type. Multiple or cross-type results are blocking. A source provider named OTHER must not become a default. Duplicate source IDs may share a target only through an explicit duplicate group with individual provenance.

Provider contact, contract, verification credentials/config, tiers and memberships have no Classic source in this table and are not invented. `InsuranceProviderSeeder` is not allowed in migration because it introduces nonsource data.

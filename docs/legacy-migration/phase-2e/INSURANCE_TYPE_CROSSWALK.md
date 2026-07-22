# Insurance type crosswalk

| Source | Count | Target payer/provider code | Rule |
|---|---:|---|---|
| PRIVATE INSURANCE | 15,654 | payer private; provider type PRIVATE or CORPORATE only through Phase 2A | target member_type not evidenced; claims deferred |
| NHIS | 15,653 | payer/type NHIA | type alone cannot select an NHIA provider; target member_type not evidenced; claims deferred |

Blank or out-of-domain values are classified exceptions. Provider classification, target patient `member_type`, payer category and claims dependency are separate outputs. Provider identity still requires a unique approved crosswalk; `InsType` alone never selects one. The crosswalk cannot be reused as a BillStatus or claim-status default.

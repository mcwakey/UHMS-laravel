# Patient BillStatus crosswalk

| Exact normalized source | Count | Payer evidence | Membership effect |
|---|---:|---|---|
| NHIS | 10,587 | nhia | none |
| PRIVATE INSURANCE | 3,416 | private | none |
| CASH AND CARRY | 2,518 | self | no insurance membership |
| PRIVATE INSURANCE + NHIS | 384 | mixed private/nhia | none; cannot select provider |
| blank | 40 | not evidenced | none |
| PRIVATE INSURANCE + | 5 | malformed exception | none |

This is payer-category corroboration, not eligibility, provider identity, currentness or visit payer context. Unknown values are exceptions; no default applies.

# Emergency-contact mapping

## Column mapping

| Classic tuple member | Target | Rule |
|---|---|---|
| `NOK` | `emergency_contacts.name` | required; Latin-1 decode, NFC, trim, control rejection, max 100; do not split or use for identity |
| `NOKPhoneNo` | `emergency_contacts.phone` | required; versioned Ghana validation; no patient-phone fallback |
| `NOKRel` | `emergency_contacts.relationship` | nullable structurally validated free text, max 50; preserve without semantic recoding, forced enum or default `Other` |
| no source | `phone_secondary` | null plus absence provenance |
| no source | `is_primary` | target-initialization rule below |
| no source | contact timestamps | null plus protected absence provenance; never migration time as history |

## Creation rule

**Phase 2 technical specification:** for a newly created patient, the sole complete valid Classic tuple becomes one migration-origin emergency/next-of-kin contact and the initial primary contact, only after a committed patient map and an atomic check that no contact/primary already exists. This is target initialization, not a claimed Classic historical primary flag. Partial/invalid contacts are withheld and never primary.

For an explicit existing-target link, create/update/delete/merge zero contacts, change zero primary flags and store only protected comparison outcomes. Multiple existing primary contacts stop contact processing; migration does not repair them.

No cross-patient or value-based contact deduplication is allowed. Retry idempotency uses the protected parent map plus a domain-separated tuple token and versions, never a contact search.

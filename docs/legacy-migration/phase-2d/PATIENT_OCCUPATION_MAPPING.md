# Patient occupation mapping

**Confirmed:** `Work` is NOT NULL varchar(15), with 10,510 nonblank rows, 1,387 normalized groups and zero controls/encoding failures. `sett_ocuupation` has zero rows. Target `patients.occupation` is nullable free text (physical 191, operational max 100); configured UI labels are not an enforced catalogue.

**Inference:** the field is occupation-like. It is not a catalogue key.

**Phase 2 technical specification:** deliberately decode Latin-1, NFC-normalize and trim. Preserve valid nonblank text directly in `patients.occupation` for a newly created patient. Blank becomes null plus absence provenance. Invalid/control/overlength text is field-level withheld without blocking the patient; no truncation occurs.

Never populate/recreate the Classic catalogue, manufacture renewed catalogue rows, force a configured label, or use occupation for matching, deduplication, payer inference or identity. Repetition is irrelevant. Existing-target values, including null, are immutable.


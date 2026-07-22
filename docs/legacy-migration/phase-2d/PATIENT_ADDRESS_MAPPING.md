# Patient address mapping

**Confirmed:** Classic has one NOT NULL varchar(25) unstructured `Address`, with 15,968 nonblank rows and zero controls/encoding failures. Target `patients.address` is nullable text with operational max 500. Target locality fields are separate; `postal_code` is absent.

**Phase 2 technical specification:** decode Latin-1, apply NFC and outer trim, and preserve valid nonblank display text only in `patients.address` for newly created patients. Blank becomes null with absence provenance. Invalid/control/overlength data is withheld at field level and never truncated.

Do not parse punctuation, geocode, infer GhanaPost GPS, city, town, region, postal code or digital address, or substitute an organisation address. Address cannot match/deduplicate a patient. Existing-target address and every locality field remain unchanged, including target blanks.

Raw addresses are restricted patient data and appear only in protected runtime provenance, never repository evidence.


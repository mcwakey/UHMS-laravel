# NOK contact-tuple contract

`NOK`, `NOKPhoneNo` and `NOKRel` form one optional tuple per source patient. They never form three independent children.

## Classification precedence

1. extraction/privacy failure;
2. parent quarantine;
3. explicit existing-target evidence-only branch;
4. all blank;
5. partial presence;
6. invalid required/optional representation;
7. complete valid.

The captured presence partition is 14,578 complete, 1,001 partial and 1,371 all blank. With the conservative local target phone shape, 14,348 complete tuples have a compatible phone candidate; final tuple validity is recalculated under the frozen migration validator.

Name normalization deliberately decodes Latin-1, applies Unicode NFC and outer trim, preserves internal display text, collapses whitespace for protected comparison only, and rejects controls/noncharacters/overlength. It never splits the name or derives it from another field.

Phone normalization removes only outer/format punctuation approved by the contract. A local `0[235]########` value remains that valid target form; a fully valid `+233[235]########` form is also target-compatible, but the captured Classic length means plus-shaped rows must be validated rather than assumed. Multiple numbers, extensions, invalid characters and lengths are withheld. The patient phone is never copied.

Relationship remains nullable free text because the target has no enum. Every structurally valid control-free value at max 50 is preserved as normalized display text with protected provenance; typographical variants are not corrected or semantically recoded. Blank remains null. Numeric/punctuation shape is retained as a diagnostic flag, not converted to `Other` and not used to infer a relationship. Decode/control/overlength failure withholds the tuple. This single rule removes the prior “withhold relationship or tuple” ambiguity.

An absent or invalid tuple never by itself invalidates the patient. A patient-level privacy/provenance or mapping failure still holds the whole chain.

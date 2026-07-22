# Reference Dependency Graph

## Order

```text
target-existing locations / organisation settings
    -> departments
        -> specialties
        -> services
            -> investigation headers
                -> investigation criteria
                    -> criterion options
        -> products
            -> product-department links
            -> product prices (blocked)
            -> drug companions (blocked)
    -> insurance types/providers
        -> service/product provider prices (blocked)
configured wards
    -> beds (blocked on per-bed ward assignment)
suppliers
    -> deferred batch dependencies
approved finance categories
    -> configured bank account (blocked on GL/currency)
```

Clinical complaint, diagnosis and procedure catalogues are independently extractable, but each has duplicate/incomplete-key gates.

## Relationship rules

The 22 source relationships and per-relationship sentinels are in [reference_relationship_rules.json](specifications/reference_relationship_rules.json) and [reference_sentinel_rules.json](specifications/reference_sentinel_rules.json). Classic declares no foreign keys; joins are evidence-backed inferences. A valid Classic join is still not a target ID: every target relation uses the future protected crosswalk.

Consumer dependencies from visits, claims, clinical history and stock are later-phase constraints. They must never force Phase 2A to invent a reference parent.

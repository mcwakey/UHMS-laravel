# Foundation architecture

```text
explicit operator command
  -> fail-closed environment/source/target preflight
  -> versioned HMAC + typed canonicalization
  -> immutable run and coordinated snapshot manifests
  -> migration runtime isolation context
  -> generic boundary validation (no importer)
  -> protected ledgers + atomic intent/checkpoint state
  -> mandatory reconciliation + aggregate-only report
  -> privacy scan + independent review
```

The operational Laravel services remain outside this path. Normal application behavior is unchanged when migration context is absent. Foundation commands default to inspection and zero business writes.

The database layer separates immutable/append-only evidence from mutable orchestration state. References to Classic and target records use purpose-separated opaque tokens; no business-table foreign key cascades originate from foundation tables.

This is the intended architecture, not current activation authority. Repository-level protected-token/keyed-integrity enforcement, persistent recovery orchestration and application-bound isolation controls remain incomplete; all commit and non-test protected-store writes are blocked until they are implemented and independently approved.

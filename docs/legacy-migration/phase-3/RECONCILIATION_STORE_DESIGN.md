# Reconciliation store design

Reconciliation stores expected, observed and difference values as exact decimal strings with measurement identity, equation/version, snapshot coordinates, classification and verdict. Every mandatory measurement must be present.

Verdicts are fail-closed: a missing measurement is `blocked_not_measured`; any nonzero difference cannot pass; unexplained differences fail separately from approved classified exceptions. A database constraint and application service both reject `passed` with nonzero difference.

Dry-run safety measures include zero source writes, zero business-domain writes and zero prohibited side effects.

Current exact-zero service/DB rules are necessary but insufficient: the command accepts an operator-provided measurement set and does not prove that every authoritative contract equation/population was measured. A complete contract-bundle-driven evaluator is still required; arbitrary zero subsets must never pass.

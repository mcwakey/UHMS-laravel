# Journey SLA / Performance Report (Phase 9.8)

`/admin/journey/analytics` — the operational performance report. Capability-gated;
reads aggregate snapshots only (never a live full scan), cached per scope+filter.

## Sections

1. **Summary cards** with period comparison arrows — total handoffs, breach rate,
   critical breaches, avg time-to-acknowledge, avg time-to-resolve, resolution rate,
   unassigned.
2. **SLA trend** (per-day volume + breached bars).
3. **Top delay causes**.
4. **Top blocking departments** (by breaches) / **Top waiting departments** (by volume).
5. **Handoff matrix** — From → To with count / breached / critical / breach rate /
   avg wait / avg ack / avg resolve.

Charts are lightweight (CSS progress bars + tables) — no heavy JS dependency, graceful
empty states ("No analytics data for this period yet.").

## Filters

Date range (default last 7 days, capped at `max_days`), cause, granularity. Filters are
preserved in the URL and validated. Unauthorized department filters cannot widen scope —
`scope_types` is applied on top of any explicit filter.

## Department vs oversight

- **Department users** see only their allowed domains (Phase 8 capabilities) — no
  cross-department leakage.
- **`journey.oversight`** sees hospital-wide analytics, the full matrix and worst
  blocking/waiting departments. Reports are **aggregate only** — no patient details,
  no action deep links unless the user also holds the relevant capability.

## Export

`?dataset=matrix|departments|causes` → CSV. **Aggregate only** — the snapshots contain
no patient-identifiable data, so neither does any export. Permission + scope are
enforced before export.

## Trend comparison

Current vs previous equal-length period: direction (up/down/flat) + % delta, guarded
against zero denominators ("not enough data").

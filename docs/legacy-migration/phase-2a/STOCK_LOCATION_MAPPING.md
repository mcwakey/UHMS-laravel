# Stock Location Mapping

[Confirmed] Classic has no stock-location master. `med_store` and `med_pharm` are product snapshots, not locations. Their row IDs and product rows must not create one target location per row.

[Technical specification] Configure exact target IDs for the two logical bindings:

- Classic store snapshot → approved active Main Store target.
- Classic pharmacy snapshot → approved active Pharmacy target.

Validate that IDs are distinct, names/types are compatible, and exactly one approved active main store exists. Target enforces unique location name but does not enforce uniqueness of `is_main`; runtime resolvers may pick first matches. Never invoke `StockLocationController::index` or department stock-sync behavior during preflight because those paths may create locations.

All snapshot quantities/prices/thresholds are deferred to stock mapping. Only product-reference reconciliation is in Phase 2A. No stock balance or movement is created.

# Ward and Bed Mapping

[Confirmed] Classic has 18 `beds` rows but no ward master or ward foreign key. Target requires `beds.ward_id` and uniquely identifies a bed by `(ward_id, bed_number)`. Therefore all 18 rows are blocked until a per-bed target ward assignment exists.

| Classic | Disposition |
|---|---|
| `BED_ID` | protected crosswalk |
| `BedName` | candidate bed number after ward configuration |
| `BedCost` | deferred daily-rate candidate; currency/effective-period semantics required |
| `BedDesc` | sanitized notes candidate |
| `BedStatus` | operational evidence only/deferred |
| `PAT_ID` | occupancy/admission dependency; never a generic patient |
| `BedDate` | mutable state timestamp; not creation proof |

Observed status is AVAILABLE 12/OCCUPIED 6. That evidence does not authorize target status. Target workflow rejects occupied status without active admission; importing AVAILABLE could release an occupied bed.

Ward identity is configured by target ward code; no ward may be inferred from bed text. Bed identity is configured ward code plus normalized bed number. Compare mapped bed count with ward capacity, but never alter capacity automatically.

`WardAndBedSeeder` is prohibited as a migration matcher because it can reset status to available.

<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

enum DdlInspectionState: string
{
    case Absent = 'absent';
    case Matching = 'expected_present_matching';
    case Drifted = 'expected_present_drifted';
    case Conflicting = 'conflicting_object';
    case RepairableMetadataGap = 'repairable_metadata_gap';
}

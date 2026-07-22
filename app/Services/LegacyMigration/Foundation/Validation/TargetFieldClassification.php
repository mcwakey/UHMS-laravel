<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

enum TargetFieldClassification: string
{
    case ApprovedValue = 'approved_value';
    case ApprovedConditionalNull = 'approved_conditional_null';
    case RecommendationPendingApproval = 'recommendation_pending_approval';
    case TargetOwnedOperationalState = 'target_owned_operational_state';
    case ProhibitedDefault = 'prohibited_default';
    case CommitBlocker = 'commit_blocker';
}

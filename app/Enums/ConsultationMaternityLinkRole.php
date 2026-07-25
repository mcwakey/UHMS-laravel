<?php

namespace App\Enums;

/**
 * Why a consultation session is linked to a maternity record.
 *
 * Phase 14R.2 bridge. Closed set — the link service validates the role, so
 * arbitrary values can never reach the database.
 */
enum ConsultationMaternityLinkRole: string
{
    /** The maternity record this consultation is primarily working within. */
    case PRIMARY = 'primary';

    /** The clinician reviewed the record without owning it. */
    case REVIEWED = 'reviewed';

    /** The record was created from this consultation. */
    case CREATED = 'created';

    /** The record was handed off to/from another workspace. */
    case HANDOFF = 'handoff';

    /** Retained for history after the encounter closed. */
    case HISTORICAL = 'historical';

    public function label(): string
    {
        return __('consultation_maternity.link_roles.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\User;

/**
 * Department-type menu profiles.
 *
 * This service ONLY affects the ORDER of sidebar sections — it surfaces the
 * sections most relevant to a user's department type near the top. It never adds
 * or removes a section: permission/module gating in SidebarMenuBuilder remains
 * the sole security layer. A user whose department type has no profile (or who
 * has no department) keeps the default order.
 */
class DepartmentMenuProfileService
{
    /**
     * Department type value → ordered list of sidebar section titles to float to
     * the top (just under "Main Menu"). Titles must match SidebarMenuBuilder.
     */
    private const PROFILES = [
        'consultation'  => ['Patient Services', 'Clinical'],
        'treatment'     => ['Clinical', 'Patient Services'],
        'nursing'       => ['Ward / Inpatient', 'Ward / Emergency', 'Clinical'],
        'emergency'     => ['Ward / Emergency', 'Patient Services', 'Clinical'],
        'investigation' => ['Investigations'],
        'radiology'     => ['Investigations'],
        'procedure'     => ['Theatre / Procedures'],
        'theatre'       => ['Theatre / Procedures'],
        'pharmacy'      => ['Pharmacy'],
        'inpatient'     => ['Ward / Inpatient', 'Ward / Emergency'],
        'maternity'     => ['Ward / Inpatient', 'Clinical'],
        'blood_bank'    => ['Blood Bank'],
        'records'       => ['Patient Services', 'Reports'],
        'finance'       => ['Billing & Collections', 'Accounts & Finance', 'Claims & Insurance'],
        'stores'        => ['Store & Procurement'],
        // administrative, support, mortuary, ambulance → default order (no profile).
    ];

    /**
     * @return list<string> section titles to prioritise for this user (may be empty).
     */
    public function prioritySectionTitles(?User $user): array
    {
        $type = $user?->department?->type;

        if (! $type instanceof DepartmentType) {
            return [];
        }

        return self::PROFILES[$type->value] ?? [];
    }

    public function profileKeyForType(?DepartmentType $type): string
    {
        return $type && isset(self::PROFILES[$type->value]) ? $type->value : 'generic';
    }

    /**
     * Reorder already-finalised (permission/module-filtered) sidebar sections so
     * the user's department-relevant sections come first, after "Main Menu".
     * Adds/removes nothing — same sections, different order.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    public function prioritise(array $sections, ?User $user): array
    {
        $priority = $this->prioritySectionTitles($user);

        if ($priority === []) {
            return $sections;
        }

        $pinned = [];
        $byTitle = [];
        $rest = [];

        foreach ($sections as $section) {
            $title = $section['title'] ?? null;

            if ($title === 'Main Menu') {
                $pinned[] = $section;
            } elseif ($title !== null && in_array($title, $priority, true)) {
                $byTitle[$title] = $section;
            } else {
                $rest[] = $section;
            }
        }

        $prioritised = [];
        foreach ($priority as $title) {
            if (isset($byTitle[$title])) {
                $prioritised[] = $byTitle[$title];
                unset($byTitle[$title]);
            }
        }

        // Any priority section not consumed (shouldn't happen) keeps natural order.
        return array_merge($pinned, $prioritised, array_values($byTitle), $rest);
    }
}

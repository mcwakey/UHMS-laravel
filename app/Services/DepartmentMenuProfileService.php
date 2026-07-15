<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\User;
use App\Services\Department\DepartmentDashboardLayoutRegistry;

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
        'consultation' => ['Patient Services', 'Clinical'],
        'treatment' => ['Clinical', 'Patient Services'],
        'nursing' => ['Nursing Workspace', 'OPD Patient Flow', 'Triage and Assessment', 'Nursing Care'],
        'emergency' => ['Ward / Emergency', 'Patient Services', 'Clinical'],
        'investigation' => ['Investigations'],
        'radiology' => ['Investigations'],
        'procedure' => ['Theatre / Procedures'],
        'theatre' => ['Theatre / Procedures'],
        'pharmacy' => ['Pharmacy'],
        'inpatient' => ['Ward / Inpatient', 'Ward / Emergency'],
        'maternity' => ['Ward / Inpatient', 'Clinical'],
        'blood_bank' => ['Blood Bank'],
        'records' => ['Patient Services', 'Reports'],
        'finance' => ['Billing & Collections', 'Accounts & Finance', 'Claims & Insurance'],
        'stores' => ['Store & Procurement'],
        // administrative, support, mortuary, ambulance → default order (no profile).
    ];

    public function __construct(
        private DepartmentDashboardLayoutRegistry $layouts,
    ) {}

    /**
     * Personalised menu / dashboard heading for a department type, e.g.
     * "Laboratory Department Workbench", "Emergency / Casualty Command Center".
     * The department NAME is identity-only — it never grants access.
     */
    public function headingForType(?DepartmentType $type, ?string $departmentName): string
    {
        if ($departmentName === null || $departmentName === '') {
            return __('departments.dashboards.'.($type?->value ?? 'generic').'.name');
        }

        $template = $this->layouts->menuHeadingTemplateFor($type);

        return __('departments.menu_profiles.'.$template, ['department' => $departmentName]);
    }

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

    public function prioritySectionTitlesForType(?DepartmentType $type): array
    {
        return $type ? (self::PROFILES[$type->value] ?? []) : [];
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

        return $this->prioritiseByTitles($sections, $priority);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    public function prioritiseForType(array $sections, ?DepartmentType $type): array
    {
        return $this->prioritiseByTitles($sections, $this->prioritySectionTitlesForType($type));
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  list<string>  $priority
     * @return array<int, array<string, mixed>>
     */
    private function prioritiseByTitles(array $sections, array $priority): array
    {

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

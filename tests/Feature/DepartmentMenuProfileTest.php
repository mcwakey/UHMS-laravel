<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentMenuProfileService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentMenuProfileTest extends TestCase
{
    use RefreshDatabase;

    private function userInDepartmentType(?DepartmentType $type): User
    {
        if ($type === null) {
            return User::factory()->create(['department_id' => null]);
        }

        $department = Department::create([
            'name' => 'Dept '.$type->value,
            'code' => strtoupper(substr($type->value, 0, 3)).rand(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);

        return User::factory()->create(['department_id' => $department->id]);
    }

    /** Synthetic finalised sidebar (the shape SidebarMenuBuilder produces). */
    private function sampleSections(): array
    {
        return array_map(fn (string $title) => ['title' => $title, 'items' => [['label' => $title]]], [
            'Main Menu',
            'Patient Services',
            'Clinical',
            'Pharmacy',
            'Investigations',
            'Accounts & Finance',
            'Billing & Collections',
            'Claims & Insurance',
            'Reports',
        ]);
    }

    public function test_priority_titles_depend_on_department_type(): void
    {
        $service = app(DepartmentMenuProfileService::class);

        $this->assertSame(['Pharmacy'], $service->prioritySectionTitles($this->userInDepartmentType(DepartmentType::PHARMACY)));
        $this->assertSame(['Investigations'], $service->prioritySectionTitles($this->userInDepartmentType(DepartmentType::RADIOLOGY)));
        $this->assertSame([], $service->prioritySectionTitles($this->userInDepartmentType(null)));
        // A type with no profile keeps default order.
        $this->assertSame([], $service->prioritySectionTitles($this->userInDepartmentType(DepartmentType::SUPPORT)));
    }

    public function test_prioritise_floats_department_sections_after_main_menu(): void
    {
        $service = app(DepartmentMenuProfileService::class);
        $user = $this->userInDepartmentType(DepartmentType::PHARMACY);

        $result = $service->prioritise($this->sampleSections(), $user);

        // Main Menu stays pinned first, Pharmacy floats to second.
        $this->assertSame('Main Menu', $result[0]['title']);
        $this->assertSame('Pharmacy', $result[1]['title']);

        // Nothing is added or removed — same set of section titles.
        $original = collect($this->sampleSections())->pluck('title')->sort()->values()->all();
        $after = collect($result)->pluck('title')->sort()->values()->all();
        $this->assertSame($original, $after);
    }

    public function test_prioritise_preserves_multi_section_priority_order(): void
    {
        $service = app(DepartmentMenuProfileService::class);
        $user = $this->userInDepartmentType(DepartmentType::FINANCE);

        $result = collect($service->prioritise($this->sampleSections(), $user))->pluck('title')->all();

        // finance profile order: Billing & Collections, Accounts & Finance, Claims & Insurance.
        $this->assertSame('Main Menu', $result[0]);
        $this->assertSame('Billing & Collections', $result[1]);
        $this->assertSame('Accounts & Finance', $result[2]);
        $this->assertSame('Claims & Insurance', $result[3]);
    }

    public function test_prioritise_is_noop_without_a_profiled_department(): void
    {
        $service = app(DepartmentMenuProfileService::class);
        $user = $this->userInDepartmentType(null);

        $sections = $this->sampleSections();
        $this->assertSame(
            collect($sections)->pluck('title')->all(),
            collect($service->prioritise($sections, $user))->pluck('title')->all(),
        );
    }

    public function test_sidebar_builder_keeps_main_menu_first_for_department_user(): void
    {
        // Pharmacy, finance, stores etc. now have dedicated workspace sidebars,
        // so use a profiled type without its own workspace (theatre) to
        // exercise the generic menu.
        $user = $this->userInDepartmentType(DepartmentType::THEATRE);

        $sections = app(SidebarMenuBuilder::class)->build($user, 'admin.dashboard');

        $this->assertNotEmpty($sections);
        $this->assertSame('Main Menu', $sections[0]['title']);
    }

    public function test_sidebar_builder_gives_pharmacy_users_the_workspace_menu(): void
    {
        $user = $this->userInDepartmentType(DepartmentType::PHARMACY);

        $sections = app(SidebarMenuBuilder::class)->build($user, 'pharmacy.dashboard');

        $this->assertNotEmpty($sections);
        $this->assertSame(__('pharmacy.workspace.title'), $sections[0]['title']);
    }
}

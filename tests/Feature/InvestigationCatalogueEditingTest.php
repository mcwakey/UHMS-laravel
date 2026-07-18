<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\InvestigationCriterion;
use App\Models\InvestigationHeader;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvestigationCatalogueEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'type' => DepartmentType::INVESTIGATION->value,
        ]);

        $role = Role::create(['name' => 'Investigation Catalogue Manager']);
        $role->givePermissionTo(Permission::findOrCreate('lab.tests.manage', 'web'));

        $this->manager = User::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->manager->assignRole($role);
    }

    public function test_catalogue_page_exposes_header_and_criterion_edit_controls(): void
    {
        $service = $this->makeService();
        $header = InvestigationHeader::create([
            'service_id' => $service->id,
            'name' => 'Panel',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        InvestigationCriterion::create([
            'service_id' => $service->id,
            'header_id' => $header->id,
            'name' => 'Result',
            'input_type' => 'text',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        // Managers in an investigation department are redirected into the
        // workspace copy of the catalogue page; the content is the same.
        $this->actingAs($this->manager)
            ->get(route('admin.investigation-catalogue.show', $service))
            ->assertRedirect(route('investigations.investigation-catalogue.show', $service));

        $this->actingAs($this->manager)
            ->get(route('investigations.investigation-catalogue.show', $service))
            ->assertOk()
            ->assertSee('edit-header-btn', false)
            ->assertSee('edit-crit-btn', false)
            ->assertSee('editHeaderModal', false)
            ->assertSee('editCriterionModal', false);
    }

    public function test_header_can_be_edited_and_nullable_values_can_be_cleared(): void
    {
        $service = $this->makeService();
        $header = InvestigationHeader::create([
            'service_id' => $service->id,
            'name' => 'Old Header',
            'description' => 'Old description',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->putJson(route('admin.investigation-catalogue.headers.update', $header), [
                'name' => 'Updated Header',
                'description' => null,
                'sort_order' => 25,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('header.name', 'Updated Header')
            ->assertJsonPath('header.is_active', false);

        $header->refresh();
        $this->assertNull($header->description);
        $this->assertSame(25, $header->sort_order);
        $this->assertFalse($header->is_active);
    }

    public function test_criterion_can_be_fully_edited_cleared_and_moved_to_another_header(): void
    {
        $service = $this->makeService();
        $source = $this->makeHeader($service, 'Source');
        $target = $this->makeHeader($service, 'Target');
        $criterion = InvestigationCriterion::create([
            'service_id' => $service->id,
            'header_id' => $source->id,
            'name' => 'Old Criterion',
            'unit' => 'mg/dL',
            'reference_range' => '1 - 5',
            'default_value' => '2',
            'input_type' => 'select',
            'options' => ['One', 'Two'],
            'sort_order' => 10,
            'is_required' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->putJson(route('admin.investigation-catalogue.criteria.update', $criterion), [
                'header_id' => $target->id,
                'name' => 'Updated Criterion',
                'unit' => null,
                'reference_range' => null,
                'default_value' => null,
                'input_type' => 'text',
                'options' => null,
                'sort_order' => 30,
                'is_required' => false,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('criterion.name', 'Updated Criterion')
            ->assertJsonPath('criterion.header_id', $target->id);

        $criterion->refresh();
        $this->assertNull($criterion->unit);
        $this->assertNull($criterion->reference_range);
        $this->assertNull($criterion->default_value);
        $this->assertNull($criterion->options);
        $this->assertFalse($criterion->is_required);
        $this->assertFalse($criterion->is_active);
    }

    public function test_criterion_cannot_be_moved_to_another_services_header(): void
    {
        $service = $this->makeService();
        $otherService = $this->makeService();
        $criterion = InvestigationCriterion::create([
            'service_id' => $service->id,
            'name' => 'Result',
            'input_type' => 'text',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $foreignHeader = $this->makeHeader($otherService, 'Foreign');

        $this->actingAs($this->manager)
            ->putJson(route('admin.investigation-catalogue.criteria.update', $criterion), [
                'header_id' => $foreignHeader->id,
                'name' => 'Result',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('header_id');
    }

    private function makeService(): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => 'Investigation ' . uniqid(),
            'code' => 'INV-' . strtoupper(uniqid()),
            'category' => 'lab',
            'price' => 50,
            'is_active' => true,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::INVESTIGATION->value,
        ]);
    }

    private function makeHeader(ServiceCatalog $service, string $name): InvestigationHeader
    {
        return InvestigationHeader::create([
            'service_id' => $service->id,
            'name' => $name,
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }
}

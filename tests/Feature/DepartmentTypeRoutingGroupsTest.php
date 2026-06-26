<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTypeRoutingGroupsTest extends TestCase
{
    use RefreshDatabase;

    private function department(DepartmentType $type, string $code): Department
    {
        return Department::create([
            'name' => 'Dept '.$type->value,
            'code' => $code,
            'type' => $type->value,
            'status' => 'active',
        ]);
    }

    public function test_routing_group_membership(): void
    {
        $this->assertSame(['procedure', 'theatre'], DepartmentType::valuesFor(DepartmentType::procedureTypes()));
        $this->assertSame(['investigation', 'radiology'], DepartmentType::valuesFor(DepartmentType::investigationTypes()));
        $this->assertSame(['inpatient', 'nursing', 'maternity'], DepartmentType::valuesFor(DepartmentType::wardTypes()));
        $this->assertSame(['consultation'], DepartmentType::valuesFor(DepartmentType::consultationTypes()));

        $this->assertTrue(DepartmentType::THEATRE->isProcedureCapable());
        $this->assertTrue(DepartmentType::RADIOLOGY->isInvestigation());
        $this->assertTrue(DepartmentType::MATERNITY->isWard());
        $this->assertFalse(DepartmentType::EMERGENCY->isConsultation());
    }

    public function test_procedure_capable_scope_includes_theatre_and_excludes_consultation(): void
    {
        $procedure = $this->department(DepartmentType::PROCEDURE, 'PRC');
        $theatre = $this->department(DepartmentType::THEATRE, 'THT');
        $consult = $this->department(DepartmentType::CONSULTATION, 'OPD');

        $ids = Department::procedureCapable()->pluck('id');

        $this->assertTrue($ids->contains($procedure->id));
        $this->assertTrue($ids->contains($theatre->id));   // retype-safe: theatre still routed for procedures
        $this->assertFalse($ids->contains($consult->id));
    }

    public function test_investigation_scope_includes_radiology(): void
    {
        $lab = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $radiology = $this->department(DepartmentType::RADIOLOGY, 'RAD');

        $ids = Department::investigation()->pluck('id');

        $this->assertTrue($ids->contains($lab->id));
        $this->assertTrue($ids->contains($radiology->id)); // retype-safe: radiology still accepts requests
    }

    public function test_consultation_scope_excludes_emergency_after_retype(): void
    {
        $opd = $this->department(DepartmentType::CONSULTATION, 'OPD');
        $emergency = $this->department(DepartmentType::EMERGENCY, 'EMR');

        $ids = Department::consultation()->pluck('id');

        $this->assertTrue($ids->contains($opd->id));
        $this->assertFalse($ids->contains($emergency->id)); // emergency has its own flow
    }

    public function test_of_types_scope_accepts_enums_and_strings(): void
    {
        $ward = $this->department(DepartmentType::INPATIENT, 'WRD');
        $this->department(DepartmentType::PHARMACY, 'PHR');

        $byEnum = Department::ofTypes(DepartmentType::wardTypes())->pluck('id');
        $byString = Department::ofTypes(['inpatient', 'nursing', 'maternity'])->pluck('id');

        $this->assertTrue($byEnum->contains($ward->id));
        $this->assertTrue($byString->contains($ward->id));
    }
}

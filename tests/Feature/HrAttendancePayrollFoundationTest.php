<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrShift;
use App\Models\LeaveRequest;
use App\Models\PayrollTaxCalculation;
use App\Models\PayrollTaxTable;
use App\Services\AttendanceProcessingService;
use App\Services\PayrollDraftService;
use App\Services\PayrollTaxCalculationService;
use Database\Seeders\GhanaPayeTaxTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAttendancePayrollFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GhanaPayeTaxTableSeeder::class);
        $department = Department::factory()->create();
        $this->employee = Employee::create([
            'employee_number' => 'EMP-9001', 'first_name' => 'Ama', 'last_name' => 'Mensah', 'phone' => '0200000000',
            'department_id' => $department->id, 'position' => 'Nurse', 'hire_date' => '2024-01-01', 'basic_salary' => 5000,
            'status' => EmployeeStatus::ACTIVE->value, 'tax_residency_status' => 'resident',
        ]);
    }

    public function test_ghana_resident_monthly_paye_table_and_progressive_examples(): void
    {
        $service = app(PayrollTaxCalculationService::class);
        $this->assertSame(0.0, $service->calculate($this->employee, 490)['tax_amount']);
        $this->assertSame(5.5, $service->calculate($this->employee, 600)['tax_amount']);
        $this->assertSame(18.5, $service->calculate($this->employee, 730)['tax_amount']);
        $this->assertSame(4692.67, $service->calculate($this->employee, 20296.67)['tax_amount']);
        $this->assertTrue(PayrollTaxTable::where('name', 'Ghana PAYE Resident Monthly 2024')->exists());
    }

    public function test_non_resident_and_exempt_tax_rules(): void
    {
        $service = app(PayrollTaxCalculationService::class);
        $this->employee->update(['tax_residency_status' => 'non_resident']);
        $this->assertSame(250.0, $service->calculate($this->employee->fresh(), 1000)['tax_amount']);
        $this->employee->update(['paye_exempt' => true]);
        $this->assertSame(0.0, $service->calculate($this->employee->fresh(), 1000)['tax_amount']);
    }

    public function test_attendance_processing_calculates_shift_exceptions_and_is_idempotent(): void
    {
        $shift = HrShift::create(['name' => 'Day', 'start_time' => '08:00', 'end_time' => '16:00', 'grace_minutes' => 10, 'break_minutes' => 0, 'is_active' => true]);
        EmployeeShiftAssignment::create(['employee_id' => $this->employee->id, 'shift_id' => $shift->id, 'assignment_type' => 'fixed', 'effective_from' => '2026-06-01', 'is_active' => true]);
        EmployeeAttendance::create(['employee_id' => $this->employee->id, 'date' => '2026-06-15', 'clock_in' => '08:20', 'clock_out' => '16:30', 'status' => 'present', 'source' => 'manual']);

        $service = app(AttendanceProcessingService::class);
        $service->process(now()->parse('2026-06-15'));
        $service->process(now()->parse('2026-06-15'));

        $attendance = EmployeeAttendance::first();
        $this->assertSame(10, $attendance->late_minutes);
        $this->assertSame(30, $attendance->overtime_minutes);
        $this->assertSame('late', $attendance->status);
        $this->assertSame(1, EmployeeAttendance::count());
    }

    public function test_approved_leave_prevents_absence(): void
    {
        LeaveRequest::create(['employee_id' => $this->employee->id, 'leave_type' => 'sick', 'start_date' => '2026-06-15', 'end_date' => '2026-06-15', 'days' => 1, 'status' => 'approved']);
        app(AttendanceProcessingService::class)->process(now()->parse('2026-06-15'));
        $this->assertSame('sick_leave', EmployeeAttendance::first()->status);
    }

    public function test_payroll_draft_requires_approved_attendance_and_stores_tax_snapshot(): void
    {
        EmployeeAttendance::create(['employee_id' => $this->employee->id, 'date' => '2026-06-15', 'status' => 'present', 'review_status' => 'approved', 'source' => 'manual']);
        $run = app(PayrollDraftService::class)->generate('2026-06');
        $record = $run->records->first();
        $this->assertSame('draft', $record->status->value);
        $this->assertGreaterThan(0, (float) $record->tax);
        $this->assertNotEmpty($record->calculation_snapshot['tax']['breakdown']);
        $this->assertTrue(PayrollTaxCalculation::where('payroll_record_id', $record->id)->exists());
    }
}

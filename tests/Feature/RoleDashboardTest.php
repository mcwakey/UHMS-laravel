<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Super Admin', 'Receptionist', 'Doctor', 'Nurse', 'Pharmacist', 'Lab Technician'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        User::factory()->create(); // id 1 for PatientFactory registered_by
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_all_four_dashboards(): void
    {
        $admin = $this->userWithRole('Super Admin');

        foreach (['receptionist', 'doctor', 'nurse', 'pharmacist'] as $dashboard) {
            $this->actingAs($admin)->get(route('admin.dashboards.'.$dashboard))->assertOk();
        }
    }

    public function test_each_role_lands_on_its_own_dashboard(): void
    {
        $this->actingAs($this->userWithRole('Receptionist'))
            ->get(route('admin.dashboards.index'))
            ->assertRedirect(route('admin.dashboards.receptionist'));

        $this->actingAs($this->userWithRole('Doctor'))
            ->get(route('admin.dashboards.index'))
            ->assertRedirect(route('admin.dashboards.doctor'));

        $this->actingAs($this->userWithRole('Nurse'))
            ->get(route('admin.dashboards.index'))
            ->assertRedirect(route('admin.dashboards.nurse'));

        $this->actingAs($this->userWithRole('Pharmacist'))
            ->get(route('admin.dashboards.index'))
            ->assertRedirect(route('admin.dashboards.pharmacist'));
    }

    public function test_roles_can_open_their_dashboard_and_others_are_forbidden(): void
    {
        $this->actingAs($this->userWithRole('Receptionist'))->get(route('admin.dashboards.receptionist'))->assertOk();
        $this->actingAs($this->userWithRole('Doctor'))->get(route('admin.dashboards.doctor'))->assertOk();
        $this->actingAs($this->userWithRole('Nurse'))->get(route('admin.dashboards.nurse'))->assertOk();
        $this->actingAs($this->userWithRole('Pharmacist'))->get(route('admin.dashboards.pharmacist'))->assertOk();

        // Cross-role access is blocked in the backend, not just hidden in the UI.
        $this->actingAs($this->userWithRole('Lab Technician'))->get(route('admin.dashboards.pharmacist'))->assertForbidden();
        $this->actingAs($this->userWithRole('Receptionist'))->get(route('admin.dashboards.nurse'))->assertForbidden();
        $this->actingAs($this->userWithRole('Pharmacist'))->get(route('admin.dashboards.doctor'))->assertForbidden();
    }

    public function test_dashboards_render_key_widgets(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->get(route('admin.dashboards.receptionist'))
            ->assertSee(__('role_dashboards.receptionist.daily_footfall'))
            ->assertSee(__('role_dashboards.receptionist.live_queue'))
            ->assertSee('footfallChart', false);

        $this->actingAs($admin)->get(route('admin.dashboards.nurse'))
            ->assertSee(__('role_dashboards.nurse.ward_occupancy'))
            ->assertSee(__('role_dashboards.nurse.weekly_ward_activity'))
            ->assertSee('activityHeatmap', false);

        $this->actingAs($admin)->get(route('admin.dashboards.pharmacist'))
            ->assertSee(__('role_dashboards.pharmacist.sales_trend'))
            ->assertSee(__('role_dashboards.pharmacist.expiry_alerts'));

        $this->actingAs($admin)->get(route('admin.dashboards.doctor'))
            ->assertSee(__('role_dashboards.doctor.my_consultation_queue'))
            ->assertSee(__('role_dashboards.doctor.next_patient'))
            ->assertSee(__('role_dashboards.doctor.results_panel'))
            ->assertSee(__('role_dashboards.doctor.clinical_activity'));
    }

    public function test_every_dashboard_renders_its_pressure_widget(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->get(route('admin.dashboards.doctor'))
            ->assertSee(__('dashboards.department.widget.waiting_pressure'));
        $this->actingAs($admin)->get(route('admin.dashboards.receptionist'))
            ->assertSee(__('dashboards.department.widget.waiting_pressure'));
        $this->actingAs($admin)->get(route('admin.dashboards.nurse'))
            ->assertSee(__('role_dashboards.nurse.medication_load'));
        $this->actingAs($admin)->get(route('admin.dashboards.pharmacist'))
            ->assertSee(__('dashboards.department.widget.dispensing_efficiency'));
    }

    public function test_doctor_dashboard_url_serves_new_dashboard_directly(): void
    {
        // /doctor is canonical; the legacy /doctor/dashboard URL redirects to it.
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/doctor/dashboard')
            ->assertRedirect(route('doctor.dashboard'));

        $this->actingAs($this->userWithRole('Doctor'))
            ->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee(__('role_dashboards.doctor.my_consultation_queue'))
            ->assertSee(__('role_dashboards.doctor.next_patient'));
    }

    public function test_doctor_sees_unassigned_routes_in_their_department(): void
    {
        $department = Department::factory()->create(['status' => 'active']);
        $doctor = $this->userWithRole('Doctor');
        $doctor->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'outpatient',
            'chief_complaint' => 'Severe headache since morning',
        ]);

        // A route in the doctor's department with NO doctor assigned yet — the
        // shared pool the consultation workbench shows.
        VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'doctor_id' => null,
            'status' => VisitConsultationRoute::STATUS_PENDING,
        ]);

        $this->actingAs($doctor)->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee($patient->full_name)
            ->assertSee('Severe headache');
    }

    public function test_localisation_files_have_matching_keys(): void
    {
        $en = require lang_path('en/role_dashboards.php');
        $fr = require lang_path('fr/role_dashboards.php');

        $flatten = function (array $a, string $p = '') use (&$flatten): array {
            $keys = [];
            foreach ($a as $k => $v) {
                $key = $p === '' ? (string) $k : $p.'.'.$k;
                is_array($v) ? $keys = array_merge($keys, $flatten($v, $key)) : $keys[] = $key;
            }
            sort($keys);

            return $keys;
        };

        $this->assertSame($flatten($en), $flatten($fr));
    }
}

<?php

namespace Tests\Feature\Journey;

use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Enums\VisitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JourneyPredictionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Permission::firstOrCreate(['name' => 'journey.predictions.view', 'guard_name' => 'web']);
    }

    private function seedHandoff(): void
    {
        $consult = Department::create(['name' => 'OPD', 'code' => 'PU'.substr(uniqid(), -6), 'type' => 'consultation', 'status' => 'active']);
        $lab = Department::create(['name' => 'Lab', 'code' => 'PL'.substr(uniqid(), -6), 'type' => 'investigation', 'status' => 'active']);
        $visit = Visit::factory()->create(['status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => User::factory()->create()->id, 'created_at' => now()->subHours(5)]);
        DB::table('visits')->where('id', $visit->id)->update(['updated_at' => now()->subHours(5)]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $visit->created_by, 'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id, 'target_department_id' => $lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function investigationUser(bool $canPredict): User
    {
        $dept = Department::create(['name' => 'Lab2', 'code' => 'PI'.substr(uniqid(), -6), 'type' => 'investigation', 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $dept->id]);
        if ($canPredict) {
            $user->givePermissionTo('journey.predictions.view');
        }

        return $user;
    }

    public function test_worklist_shows_risk_for_authorized_user(): void
    {
        $this->seedHandoff();

        $this->actingAs($this->investigationUser(true))
            ->get(route('admin.journey.worklist', ['tab' => 'owed_by']))
            ->assertOk()
            ->assertSee(__('journey.risk.col_risk'), false);
    }

    public function test_worklist_hides_risk_without_permission(): void
    {
        $this->seedHandoff();

        $this->actingAs($this->investigationUser(false))
            ->get(route('admin.journey.worklist', ['tab' => 'owed_by']))
            ->assertOk()
            ->assertDontSee(__('journey.risk.col_risk'), false);
    }

    public function test_analytics_page_renders_with_prediction_permission(): void
    {
        $this->seedHandoff();

        $this->actingAs($this->investigationUser(true))
            ->get(route('admin.journey.analytics'))
            ->assertOk();
    }
}

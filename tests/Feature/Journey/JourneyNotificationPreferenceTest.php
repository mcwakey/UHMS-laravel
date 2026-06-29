<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyHandoffNotificationEvent;
use App\Models\User;
use App\Services\Journey\JourneyNotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JourneyNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): JourneyNotificationPreferenceService
    {
        return app(JourneyNotificationPreferenceService::class);
    }

    public function test_defaults_work_without_a_row(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->service()->allows($user, JourneyHandoffNotificationEvent::ASSIGNED));
        $this->assertTrue($this->service()->allows($user, JourneyHandoffNotificationEvent::CRITICAL));
        // Noisy near-breach is off by default.
        $this->assertFalse($this->service()->allows($user, JourneyHandoffNotificationEvent::UNASSIGNED_NEAR_BREACH));
    }

    public function test_email_and_sms_disabled_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service()->allows($user, JourneyHandoffNotificationEvent::CRITICAL, 'email'));
        $this->assertFalse($this->service()->allows($user, JourneyHandoffNotificationEvent::CRITICAL, 'sms'));
    }

    public function test_preference_disables_a_notification(): void
    {
        $user = User::factory()->create();
        $this->service()->update($user, ['handoff_assigned' => ['in_app' => false]]);

        $this->assertFalse($this->service()->allows($user->fresh(), JourneyHandoffNotificationEvent::ASSIGNED));
    }

    public function test_update_is_audited(): void
    {
        $user = User::factory()->create();

        $this->service()->update($user, ['handoff_critical' => ['in_app' => false]]);

        $this->assertTrue(DB::table('activity_log')->where('event', 'JOURNEY_NOTIFICATION_PREFERENCES_UPDATED')->exists());
    }

    public function test_settings_page_renders_for_any_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.settings.journey-notifications'))->assertOk();
    }
}

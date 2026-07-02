<?php

namespace Tests\Feature\Security;

use App\Enums\JourneyDelayCause;
use App\Enums\JourneyHandoffNotificationEvent;
use App\Models\JourneyHandoffAssignment;
use App\Models\JourneyNotificationPreference;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class JourneySettingsRoutePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->securityPermissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    public function test_mutation_routes_have_explicit_permission_middleware(): void
    {
        $expected = [
            'admin.settings.journey-notifications.update' => 'can:settings.journey_notifications.update',
            'admin.journey.handoffs.claim' => 'can:journey.handoffs.claim',
            'admin.journey.handoffs.assign' => 'can:journey.handoffs.assign',
            'admin.journey.handoffs.acknowledge' => 'can:journey.handoffs.acknowledge',
            'admin.journey.handoffs.resolve' => 'can:journey.handoffs.resolve',
        ];

        foreach ($expected as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertContains($middleware, $route->gatherMiddleware(), "Route [{$routeName}] is missing [{$middleware}].");
        }
    }

    public function test_unauthorised_user_cannot_update_journey_notification_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.journey-notifications.update'), $this->preferencePayload())
            ->assertForbidden();
    }

    public function test_authorised_user_can_update_journey_notification_settings(): void
    {
        $user = $this->userWith('settings.journey_notifications.update');
        $event = JourneyHandoffNotificationEvent::ASSIGNED->value;

        $this->actingAs($user)
            ->from(route('admin.settings.journey-notifications'))
            ->put(route('admin.settings.journey-notifications.update'), $this->preferencePayload())
            ->assertRedirect(route('admin.settings.journey-notifications'));

        $this->assertDatabaseHas('journey_notification_preferences', [
            'user_id' => $user->id,
            'event' => $event,
            'in_app_enabled' => true,
            'email_enabled' => false,
            'sms_enabled' => false,
        ]);
    }

    public function test_unauthorised_user_cannot_claim_handoff(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.journey.handoffs.claim'), $this->handoffPayload())
            ->assertForbidden();
    }

    public function test_unauthorised_user_cannot_assign_handoff(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.journey.handoffs.assign'), $this->handoffPayload(['assignee_id' => User::factory()->create()->id]))
            ->assertForbidden();
    }

    public function test_unauthorised_user_cannot_acknowledge_handoff(): void
    {
        $assignment = $this->assignment();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.journey.handoffs.acknowledge', $assignment))
            ->assertForbidden();
    }

    public function test_unauthorised_user_cannot_resolve_handoff(): void
    {
        $assignment = $this->assignment();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.journey.handoffs.resolve', $assignment), ['note' => 'Done'])
            ->assertForbidden();
    }

    public function test_authorised_user_can_reach_permitted_handoff_mutation_route(): void
    {
        $this->actingAs($this->userWith('journey.handoffs.claim'))
            ->from(route('admin.journey.worklist'))
            ->post(route('admin.journey.handoffs.claim'), $this->handoffPayload(['visit_id' => 0]))
            ->assertRedirect(route('admin.journey.worklist'));
    }

    public function test_permissions_audit_strict_passes(): void
    {
        $this->artisan('db:seed', [
            '--class' => RoleSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('permissions:audit --strict')->assertExitCode(0);
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    /** @return array<string, array<string, bool>> */
    private function preferencePayload(): array
    {
        return [
            'preferences' => [
                JourneyHandoffNotificationEvent::ASSIGNED->value => [
                    'in_app' => true,
                    'email' => false,
                    'sms' => false,
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $overrides */
    private function handoffPayload(array $overrides = []): array
    {
        return array_merge([
            'visit_id' => 1,
            'cause' => JourneyDelayCause::AWAITING_LAB_RESULT->value,
        ], $overrides);
    }

    private function assignment(): JourneyHandoffAssignment
    {
        return JourneyHandoffAssignment::create([
            'visit_id' => 1,
            'cause' => JourneyDelayCause::AWAITING_LAB_RESULT,
            'status' => JourneyHandoffAssignment::STATUS_ASSIGNED,
            'escalation_level' => JourneyHandoffAssignment::ESCALATION_NONE,
        ]);
    }

    /** @return list<string> */
    private function securityPermissions(): array
    {
        return [
            'settings.journey_notifications.update',
            'journey.handoffs.claim',
            'journey.handoffs.assign',
            'journey.handoffs.acknowledge',
            'journey.handoffs.resolve',
        ];
    }
}

<?php

namespace Tests\Feature\Localization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phase 16 localisation gate: critical GET pages render in French mode without
 * a server error, and authenticated app pages report the French <html lang>.
 *
 * A "Super Admin" user is used because AppServiceProvider registers a
 * Gate::before bypass for that role, so permission gating cannot mask a
 * localisation/render failure. The curated list targets module landing/index
 * pages that render on an empty database (no model-bound show pages).
 *
 * Database content is never asserted — only HTTP status and the locale marker.
 */
class FrenchRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Curated critical active-runtime routes across the major modules. */
    private const ADMIN_ROUTES = [
        'admin/dashboard',
        'admin/patients',
        'admin/visits',
        'admin/consultations',
        'admin/appointments',
        'admin/billing/invoices',
        'admin/billing/payments',
        'admin/pharmacy/dispensing',
        'admin/pharmacy/history',
        'admin/lab/results',
        'admin/lab/tests',
        'admin/investigations/items',
        'admin/wards',
        'admin/theatre',
        'admin/theatre/rooms',
        'admin/product-stock/balances',
        'admin/store/purchase-orders',
        'admin/store/suppliers',
        'admin/accounting',
        'admin/accounting/settings',
        'admin/accounts/categories',
        'admin/reports',
        'admin/notifications',
        'admin/queue/board',
        'admin/service-renderings',
        'admin/hr/employees',
        'admin/blood-bank',
        'admin/icd-codes',
        'admin/departments',
        'admin/analyzers',
        'admin/designations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the application roles/permissions so controllers that reference
        // named roles (e.g. role('Doctor')) resolve on the empty test database.
        $this->seed(\Database\Seeders\RoleSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function superAdminInFrench(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['locale' => 'fr']);
        $user->assignRole(Role::findByName('Super Admin', 'web'));

        return $user;
    }

    public function test_login_page_renders_in_french(): void
    {
        $response = $this->withSession(['locale' => 'fr'])->get('/login');

        $response->assertStatus(200);
        $this->assertStringContainsString('lang="fr"', $response->getContent());
    }

    public function test_critical_admin_pages_render_in_french_without_server_errors(): void
    {
        $user = $this->superAdminInFrench();

        $serverErrors = [];
        $missingLocaleMarker = [];

        foreach (self::ADMIN_ROUTES as $uri) {
            $response = $this->actingAs($user)->get('/'.$uri);
            $status = $response->status();

            // A 5xx means a hard failure (render/translation exception) — never acceptable.
            if ($status >= 500) {
                $serverErrors[] = "{$uri} → HTTP {$status}";
                continue;
            }

            // Successful HTML responses must carry the French locale marker.
            if ($status === 200) {
                $content = $response->getContent();
                if (! str_contains($content, 'lang="fr"')) {
                    $missingLocaleMarker[] = $uri;
                }
            }
            // 302/403 (redirect or a non-permission gate) are tolerated for smoke purposes.
        }

        $this->assertSame(
            [],
            $serverErrors,
            "French smoke test hit server errors on:\n - ".implode("\n - ", $serverErrors)
        );

        $this->assertSame(
            [],
            $missingLocaleMarker,
            "These pages returned 200 but not in French (missing lang=\"fr\"):\n - ".implode("\n - ", $missingLocaleMarker)
        );
    }
}

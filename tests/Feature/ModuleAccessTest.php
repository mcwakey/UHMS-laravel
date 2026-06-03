<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * A regular authenticated user — NOT a Super Admin, who would bypass the
     * disabled-module gate via the `modules.override_disabled` permission
     * (granted to Super Admin through Gate::before for incident response).
     */
    private function regularUser(): User
    {
        return User::factory()->create();
    }

    private function disableModule(string $slug): void
    {
        Module::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => ucfirst($slug),
                'is_core' => false,
                'is_enabled' => false,
                'sort_order' => 100,
            ],
        );

        app(ModuleService::class)->flush();
    }

    public function test_disabled_module_blocks_direct_html_access(): void
    {
        $this->disableModule('pharmacy');

        $response = $this
            ->actingAs($this->regularUser())
            ->get(route('admin.pharmacy.dispensing.index'));

        // Phase 4 contract: a disabled module returns a friendly 403 page (not a raw
        // 404/exception) telling authenticated staff the module is off.
        $response->assertForbidden();
        $response->assertSee('Pharmacy', false);
    }

    public function test_disabled_module_blocks_direct_json_access(): void
    {
        $this->disableModule('pharmacy');

        $response = $this
            ->actingAs($this->regularUser())
            ->getJson(route('admin.pharmacy.dispensing.index'));

        // Clean JSON 403 (no stack trace) — see App\Http\Middleware\EnsureModuleEnabled.
        $response
            ->assertStatus(403)
            ->assertJson([
                // Middleware uses the module's display name ("Pharmacy"), not the slug.
                'message' => 'The Pharmacy module is currently disabled.',
            ]);
    }
}

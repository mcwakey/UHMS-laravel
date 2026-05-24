<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
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
            ->actingAs($this->superAdmin())
            ->get(route('admin.pharmacy.dispensing.index'));

        $response->assertNotFound();
    }

    public function test_disabled_module_blocks_direct_json_access(): void
    {
        $this->disableModule('pharmacy');

        $response = $this
            ->actingAs($this->superAdmin())
            ->getJson(route('admin.pharmacy.dispensing.index'));

        $response
            ->assertStatus(503)
            ->assertJson([
                'message' => 'The pharmacy module is currently disabled.',
            ]);
    }
}

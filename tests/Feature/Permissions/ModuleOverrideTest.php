<?php

namespace Tests\Feature\Permissions;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Models\Module;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_module_returns_404_for_regular_user(): void
    {
        Module::firstOrCreate(
            ['slug' => 'pharmacy'],
            ['name' => 'Pharmacy', 'is_core' => false, 'is_enabled' => false, 'sort_order' => 50]
        );

        $user = User::factory()->create();
        $request = Request::create('/admin/pharmacy', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureModuleEnabled(app(ModuleService::class));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, fn () => response('ok'), 'pharmacy');
    }

    public function test_disabled_module_passes_through_for_user_with_override(): void
    {
        Permission::firstOrCreate(['name' => 'modules.override_disabled']);

        Module::firstOrCreate(
            ['slug' => 'pharmacy'],
            ['name' => 'Pharmacy', 'is_core' => false, 'is_enabled' => false, 'sort_order' => 50]
        );

        $user = User::factory()->create();
        $user->givePermissionTo('modules.override_disabled');

        $request = Request::create('/admin/pharmacy', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureModuleEnabled(app(ModuleService::class));
        $response = $middleware->handle($request, fn () => response('ok'), 'pharmacy');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_enabled_module_passes_through_normally(): void
    {
        Module::firstOrCreate(
            ['slug' => 'patients'],
            ['name' => 'Patients', 'is_core' => true, 'is_enabled' => true, 'sort_order' => 10]
        );

        $user = User::factory()->create();
        $request = Request::create('/admin/patients', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureModuleEnabled(app(ModuleService::class));
        $response = $middleware->handle($request, fn () => response('ok'), 'patients');

        $this->assertSame(200, $response->getStatusCode());
    }
}

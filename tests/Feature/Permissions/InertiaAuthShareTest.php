<?php

namespace Tests\Feature\Permissions;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InertiaAuthShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_share_includes_permissions_roles_and_modules(): void
    {
        Permission::firstOrCreate(['name' => 'patients.view']);
        Permission::firstOrCreate(['name' => 'patients.delete']);

        $role = Role::firstOrCreate(['name' => 'Test Inertia Role']);
        $role->syncPermissions(['patients.view', 'patients.delete']);

        Module::firstOrCreate(
            ['slug' => 'patients'],
            ['name' => 'Patients', 'is_core' => true, 'is_enabled' => true, 'sort_order' => 10]
        );
        Module::firstOrCreate(
            ['slug' => 'pharmacy'],
            ['name' => 'Pharmacy', 'is_core' => false, 'is_enabled' => false, 'sort_order' => 50]
        );

        $user = User::factory()->create();
        $user->assignRole($role);

        $request = \Illuminate\Http\Request::create('/whatever', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\HandleInertiaRequests();
        $shared = $middleware->share($request);

        $this->assertArrayHasKey('auth', $shared);
        $auth = is_callable($shared['auth']) ? $shared['auth']() : $shared['auth'];

        $this->assertSame($user->id, $auth['user']['id']);

        $perms = is_callable($auth['permissions']) ? $auth['permissions']() : $auth['permissions'];
        $this->assertContains('patients.view', $perms);
        $this->assertContains('patients.delete', $perms);

        $roles = is_callable($auth['roles']) ? $auth['roles']() : $auth['roles'];
        $this->assertContains('Test Inertia Role', $roles);

        $modules = is_callable($auth['modules']) ? $auth['modules']() : $auth['modules'];
        $this->assertContains('patients', $modules);
        $this->assertNotContains('pharmacy', $modules, 'Disabled modules must not appear in auth.modules');
    }
}

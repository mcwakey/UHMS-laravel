<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.5 — additive handoff permissions.
 *
 * These gate the BRIDGE only. Every action additionally requires the target
 * module's own permission (checked in the controllers), so holding a bridge
 * permission can never grant Maternity, Emergency or Admission access a user
 * does not already have.
 *
 * Grants follow the existing convention: a role receives a bridge permission
 * only when it already holds BOTH sides of that bridge. No existing permission
 * is removed or altered.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> permission => the permissions a role must already hold */
    private array $permissions = [
        // Consultation → Admission / Obstetrics / Postnatal
        'consultation.maternity_context.create_admission_request' => ['admission.requests.create'],
        'consultation.maternity_context.refer_obstetrics' => ['consultations.create'],
        'consultation.maternity_context.open_postnatal' => ['maternity.postnatal.view'],

        // Emergency ↔ Maternity
        'emergency.maternity_context.view' => ['maternity.pregnancy.view'],
        'emergency.maternity_context.link' => ['maternity.pregnancy.view'],
        'emergency.maternity_context.unlink' => ['maternity.pregnancy.view'],
        'emergency.maternity_context.create_profile' => ['maternity.pregnancy.create'],
        'emergency.maternity_context.start_labor' => ['maternity.labor.start'],
        'emergency.maternity_context.create_admission_request' => ['admission.requests.create'],

        // Admission ↔ Maternity
        'admission.maternity_context.view' => ['maternity.pregnancy.view'],
        'admission.maternity_context.link' => ['maternity.pregnancy.view'],
        'admission.maternity_context.unlink' => ['maternity.pregnancy.view'],

        // Maternity → Emergency
        'maternity.emergency_handoff.create' => ['emergency.case.create'],
    ];

    /** The module-side permission a role must hold to receive each bridge. */
    private array $sourceRequirement = [
        'consultation.maternity_context.create_admission_request' => 'consultation',
        'consultation.maternity_context.refer_obstetrics' => 'consultation',
        'consultation.maternity_context.open_postnatal' => 'consultation',
        'emergency.maternity_context.view' => 'emergency',
        'emergency.maternity_context.link' => 'emergency',
        'emergency.maternity_context.unlink' => 'emergency',
        'emergency.maternity_context.create_profile' => 'emergency',
        'emergency.maternity_context.start_labor' => 'emergency',
        'emergency.maternity_context.create_admission_request' => 'emergency',
        'admission.maternity_context.view' => 'admission',
        'admission.maternity_context.link' => 'admission',
        'admission.maternity_context.unlink' => 'admission',
        'maternity.emergency_handoff.create' => 'maternity',
    ];

    /** @var array<string, list<string>> any-of permissions proving source-module access */
    private array $sourceAccess = [
        'consultation' => ['consultation.access', 'consultations.view'],
        'emergency' => ['emergency.case.view', 'emergency.board.view'],
        'admission' => ['ward.view', 'admission.requests.view'],
        'maternity' => ['maternity.view', 'maternity.pregnancy.view'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys($this->permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['Super Admin', 'Admin'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo(array_keys($this->permissions));
        }

        foreach (Role::with('permissions')->where('guard_name', 'web')->get() as $role) {
            if (in_array($role->name, ['Super Admin', 'Admin'], true)) {
                continue;
            }

            // Read the role's own permission names — hasPermissionTo() throws
            // when a permission name does not exist in this installation.
            $held = $role->permissions->pluck('name');

            foreach ($this->permissions as $permission => $targetRequirements) {
                $module = $this->sourceRequirement[$permission];

                $hasSource = collect($this->sourceAccess[$module])
                    ->contains(fn ($name) => $held->contains($name));

                $hasTarget = collect($targetRequirements)
                    ->every(fn ($name) => $held->contains($name));

                if ($hasSource && $hasTarget) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = array_keys($this->permissions);

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->revokePermissionTo($names);
        }

        Permission::whereIn('name', $names)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

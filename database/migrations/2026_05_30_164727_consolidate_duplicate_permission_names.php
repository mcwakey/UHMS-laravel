<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Consolidate duplicate permission names that diverged across feature work.
 *
 * For each (orphan -> canonical) pair: copy every role-permission and
 * user-permission assignment to the canonical permission, then delete
 * the orphan. Idempotent: silently skips pairs whose orphan is already gone.
 */
return new class extends Migration
{
    private array $merges = [
        // orphan                       => canonical (used by routes)
        'procedure.catalogue.view'     => 'procedure_catalogue.view',
        'procedure.catalogue.manage'   => 'procedure_catalogue.manage',
        'emergency.report.view'        => 'emergency.reports.view',
        'stock_location.manage'        => 'stock.location.manage',
        'product.link_department'      => 'product.link_departments',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->merges as $orphanName => $canonicalName) {
            $orphan = Permission::where('name', $orphanName)->first();
            if (! $orphan) {
                continue;
            }

            // Ensure the canonical permission exists (may not yet be seeded).
            $canonical = Permission::firstOrCreate(['name' => $canonicalName]);

            // Copy role assignments
            DB::table('role_has_permissions')
                ->where('permission_id', $orphan->id)
                ->get()
                ->each(function ($row) use ($canonical) {
                    DB::table('role_has_permissions')->updateOrInsert(
                        ['permission_id' => $canonical->id, 'role_id' => $row->role_id],
                        []
                    );
                });

            // Copy direct user assignments
            DB::table('model_has_permissions')
                ->where('permission_id', $orphan->id)
                ->get()
                ->each(function ($row) use ($canonical) {
                    DB::table('model_has_permissions')->updateOrInsert(
                        [
                            'permission_id' => $canonical->id,
                            'model_type'    => $row->model_type,
                            'model_id'      => $row->model_id,
                        ],
                        []
                    );
                });

            // Detach + delete orphan
            DB::table('role_has_permissions')->where('permission_id', $orphan->id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $orphan->id)->delete();
            $orphan->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Re-create the orphan permissions so a rollback restores the row.
        // Role/user assignments are NOT restored — the merge is one-way.
        foreach (array_keys($this->merges) as $orphanName) {
            Permission::firstOrCreate(['name' => $orphanName]);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasColumn('visits', 'assigned_doctor_id')) {
            return;
        }

        $this->backfillConsultationRouteDoctors();
        $this->dropAssignedDoctorForeignKey();

        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('assigned_doctor_id');
        });
    }

    public function down(): void
    {
        if (! $this->hasColumn('visits', 'assigned_doctor_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_doctor_id')->nullable();
            });

            $this->addAssignedDoctorForeignKey();
        }

        if (! Schema::hasTable('visit_consultation_routes')) {
            return;
        }

        DB::table('visit_consultation_routes')
            ->whereNotNull('doctor_id')
            ->orderBy('id')
            ->select(['visit_id', 'doctor_id'])
            ->chunk(200, function ($routes) {
                foreach ($routes as $route) {
                    DB::table('visits')
                        ->where('id', $route->visit_id)
                        ->whereNull('assigned_doctor_id')
                        ->update(['assigned_doctor_id' => $route->doctor_id]);
                }
            });
    }

    private function backfillConsultationRouteDoctors(): void
    {
        if (! Schema::hasTable('visit_consultation_routes')) {
            return;
        }

        $columns = ['id', 'assigned_doctor_id'];
        if ($this->hasColumn('visits', 'current_department_id')) {
            $columns[] = 'current_department_id';
        }

        DB::table('visits')
            ->whereNotNull('assigned_doctor_id')
            ->orderBy('id')
            ->select($columns)
            ->chunkById(200, function ($visits) {
                foreach ($visits as $visit) {
                    $routeId = $this->matchingRouteId($visit);

                    if ($routeId) {
                        DB::table('visit_consultation_routes')
                            ->where('id', $routeId)
                            ->update(['doctor_id' => $visit->assigned_doctor_id]);
                    }
                }
            });
    }

    private function matchingRouteId(object $visit): ?int
    {
        $base = DB::table('visit_consultation_routes')
            ->where('visit_id', $visit->id)
            ->whereNull('doctor_id')
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")
            ->orderBy('id');

        if (! empty($visit->current_department_id)) {
            $route = (clone $base)
                ->where('department_id', $visit->current_department_id)
                ->first();

            if ($route) {
                return (int) $route->id;
            }
        }

        $route = $base->first();

        return $route ? (int) $route->id : null;
    }

    private function dropAssignedDoctorForeignKey(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'visits'
              AND COLUMN_NAME = 'assigned_doctor_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if ($constraint?->name) {
            DB::statement("ALTER TABLE `visits` DROP FOREIGN KEY `{$constraint->name}`");
        }
    }

    private function addAssignedDoctorForeignKey(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE `visits`
            ADD CONSTRAINT `visits_assigned_doctor_id_foreign`
            FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ');
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $result = DB::selectOne('
            SELECT COUNT(*) AS cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ', [$table, $column]);

        return $result && (int) $result->cnt > 0;
    }
};

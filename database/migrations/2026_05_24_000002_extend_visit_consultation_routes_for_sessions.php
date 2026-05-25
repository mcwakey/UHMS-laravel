<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->upSqlite();

            return;
        }

        $routeTableExists = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visit_consultation_routes'
        ");

        if ($routeTableExists && (int) $routeTableExists->cnt > 0) {
            $existingColumns = collect(DB::select("
                SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visit_consultation_routes'
            "))->pluck('name')->map(fn ($name) => strtolower($name))->all();

            $adds = [];
            if (! in_array('activated_at', $existingColumns, true)) {
                $adds[] = 'ADD COLUMN `activated_at` TIMESTAMP NULL DEFAULT NULL';
            }
            if (! in_array('paused_at', $existingColumns, true)) {
                $adds[] = 'ADD COLUMN `paused_at` TIMESTAMP NULL DEFAULT NULL';
            }
            if (! in_array('cancelled_at', $existingColumns, true)) {
                $adds[] = 'ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL';
            }
            if (! in_array('cancelled_by', $existingColumns, true)) {
                $adds[] = 'ADD COLUMN `cancelled_by` BIGINT UNSIGNED NULL';
            }
            if (! in_array('cancellation_reason', $existingColumns, true)) {
                $adds[] = 'ADD COLUMN `cancellation_reason` TEXT NULL';
            }

            if ($adds) {
                DB::statement('ALTER TABLE `visit_consultation_routes` '.implode(', ', $adds));
            }
        }

        DB::statement('
            CREATE TABLE IF NOT EXISTS `visit_consultation_route_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `visit_consultation_route_id` BIGINT UNSIGNED NOT NULL,
                `visit_id` BIGINT UNSIGNED NOT NULL,
                `from_status` VARCHAR(20) NULL,
                `to_status` VARCHAR(20) NOT NULL,
                `action` VARCHAR(50) NOT NULL,
                `notes` TEXT NULL,
                `performed_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `vcr_logs_route_created_idx` (`visit_consultation_route_id`, `created_at`),
                KEY `vcr_logs_visit_created_idx` (`visit_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('visit_consultation_route_logs');

            if (Schema::hasTable('visit_consultation_routes')) {
                Schema::table('visit_consultation_routes', function (Blueprint $table) {
                    foreach ([
                        'cancellation_reason',
                        'cancelled_by',
                        'cancelled_at',
                        'paused_at',
                        'activated_at',
                    ] as $column) {
                        if (Schema::hasColumn('visit_consultation_routes', $column)) {
                            $table->dropColumn($column);
                        }
                    }
                });
            }

            return;
        }

        DB::statement('DROP TABLE IF EXISTS `visit_consultation_route_logs`');

        $routeTableExists = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visit_consultation_routes'
        ");

        if (! $routeTableExists || (int) $routeTableExists->cnt === 0) {
            return;
        }

        $existingColumns = collect(DB::select("
            SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visit_consultation_routes'
        "))->pluck('name')->map(fn ($name) => strtolower($name))->all();

        $drops = [];
        foreach (['cancellation_reason', 'cancelled_by', 'cancelled_at', 'paused_at', 'activated_at'] as $column) {
            if (in_array($column, $existingColumns, true)) {
                $drops[] = "DROP COLUMN `{$column}`";
            }
        }

        if ($drops) {
            DB::statement('ALTER TABLE `visit_consultation_routes` '.implode(', ', $drops));
        }
    }

    private function upSqlite(): void
    {
        if (Schema::hasTable('visit_consultation_routes')) {
            Schema::table('visit_consultation_routes', function (Blueprint $table) {
                if (! Schema::hasColumn('visit_consultation_routes', 'activated_at')) {
                    $table->timestamp('activated_at')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'paused_at')) {
                    $table->timestamp('paused_at')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'cancelled_by')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                }
                if (! Schema::hasColumn('visit_consultation_routes', 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable();
                }
            });
        }

        if (! Schema::hasTable('visit_consultation_route_logs')) {
            Schema::create('visit_consultation_route_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('visit_consultation_route_id');
                $table->unsignedBigInteger('visit_id');
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->string('action', 50);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamps();

                $table->index(['visit_consultation_route_id', 'created_at'], 'vcr_logs_route_created_idx');
                $table->index(['visit_id', 'created_at'], 'vcr_logs_visit_created_idx');
            });
        }
    }
};

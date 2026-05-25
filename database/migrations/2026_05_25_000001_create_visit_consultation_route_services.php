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

        $this->makeRouteServiceNullableMysql();

        DB::statement('
            CREATE TABLE IF NOT EXISTS `visit_consultation_route_services` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `visit_consultation_route_id` BIGINT UNSIGNED NOT NULL,
                `visit_id` BIGINT UNSIGNED NOT NULL,
                `service_id` BIGINT UNSIGNED NOT NULL,
                `invoice_item_id` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `vcrs_route_service_unique` (`visit_consultation_route_id`, `service_id`),
                KEY `vcrs_visit_service_idx` (`visit_id`, `service_id`),
                KEY `vcrs_invoice_item_idx` (`invoice_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $this->copyExistingRouteServices();
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_consultation_route_services');
    }

    private function upSqlite(): void
    {
        if (Schema::hasTable('visit_consultation_routes') && Schema::hasColumn('visit_consultation_routes', 'service_id')) {
            Schema::table('visit_consultation_routes', function (Blueprint $table) {
                $table->unsignedBigInteger('service_id')->nullable()->change();
            });
        }

        if (! Schema::hasTable('visit_consultation_route_services')) {
            Schema::create('visit_consultation_route_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('visit_consultation_route_id');
                $table->unsignedBigInteger('visit_id');
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('invoice_item_id')->nullable();
                $table->timestamps();

                $table->unique(['visit_consultation_route_id', 'service_id'], 'vcrs_route_service_unique');
                $table->index(['visit_id', 'service_id'], 'vcrs_visit_service_idx');
                $table->index('invoice_item_id', 'vcrs_invoice_item_idx');
            });
        }

        $this->copyExistingRouteServices();
    }

    private function makeRouteServiceNullableMysql(): void
    {
        $exists = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'visit_consultation_routes'
              AND COLUMN_NAME = 'service_id'
        ");

        if (! $exists || (int) $exists->cnt === 0) {
            return;
        }

        DB::statement('ALTER TABLE `visit_consultation_routes` MODIFY `service_id` BIGINT UNSIGNED NULL');
    }

    private function copyExistingRouteServices(): void
    {
        if (! $this->tableExists('visit_consultation_routes') || ! $this->tableExists('visit_consultation_route_services')) {
            return;
        }

        $routes = DB::table('visit_consultation_routes')
            ->whereNotNull('service_id')
            ->select(['id', 'visit_id', 'service_id'])
            ->orderBy('id')
            ->get();

        foreach ($routes as $route) {
            $invoiceItem = DB::table('invoice_items')
                ->where('visit_id', $route->visit_id)
                ->where('service_catalog_id', $route->service_id)
                ->orderBy('id')
                ->first();

            DB::table('visit_consultation_route_services')->updateOrInsert(
                [
                    'visit_consultation_route_id' => $route->id,
                    'service_id' => $route->service_id,
                ],
                [
                    'visit_id' => $route->visit_id,
                    'invoice_item_id' => $invoiceItem?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        $exists = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        return $exists && (int) $exists->cnt > 0;
    }
};

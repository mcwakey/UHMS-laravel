<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-department consultation routing history.
 *
 * One row per (visit, consultation service) the patient is routed to. Lifecycle:
 *   PENDING   – created when service is selected at visit creation
 *   ACTIVE    – set when triage / a doctor activates this specific route
 *   COMPLETED – set when the consultation is finished or patient is referred onward
 *   CANCELLED – set if the route is voided
 *
 * Multiple routes per visit support the "refer to another consultation" flow
 * while keeping the visit status as CONSULTING / WAITING_CONSULTATION throughout.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasTable('visit_consultation_routes')) {
                Schema::create('visit_consultation_routes', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('visit_id');
                    $table->unsignedBigInteger('patient_id');
                    $table->unsignedBigInteger('department_id');
                    $table->unsignedBigInteger('service_id'); // FK -> service_catalog.id
                    $table->unsignedBigInteger('doctor_id')->nullable();
                    $table->string('status', 20)->default('PENDING');
                    $table->unsignedBigInteger('routed_by')->nullable();
                    $table->unsignedBigInteger('started_by')->nullable();
                    $table->unsignedBigInteger('completed_by')->nullable();
                    $table->timestamp('started_at')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();

                    $table->index(['visit_id', 'status']);
                    $table->index(['patient_id']);
                    $table->index(['department_id', 'status']);
                });
            }
            return;
        }

        // MariaDB / MySQL: raw SQL to avoid Schema::hasTable() / Blueprint
        // introspection issues on MariaDB 10.1.32 (generation_expression column).
        $exists = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visit_consultation_routes'
        ");
        if ($exists && (int) $exists->cnt > 0) {
            return;
        }

        DB::statement("
            CREATE TABLE IF NOT EXISTS `visit_consultation_routes` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `visit_id` BIGINT UNSIGNED NOT NULL,
                `patient_id` BIGINT UNSIGNED NOT NULL,
                `department_id` BIGINT UNSIGNED NOT NULL,
                `service_id` BIGINT UNSIGNED NOT NULL,
                `doctor_id` BIGINT UNSIGNED NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'PENDING',
                `routed_by` BIGINT UNSIGNED NULL,
                `started_by` BIGINT UNSIGNED NULL,
                `completed_by` BIGINT UNSIGNED NULL,
                `started_at` TIMESTAMP NULL DEFAULT NULL,
                `completed_at` TIMESTAMP NULL DEFAULT NULL,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `vcr_visit_status_idx` (`visit_id`, `status`),
                KEY `vcr_patient_idx` (`patient_id`),
                KEY `vcr_dept_status_idx` (`department_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_consultation_routes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Use a fresh Schema::create — works fine on MariaDB; the
        // "generation_expression" bug only affects Schema::table on
        // pre-existing tables. New table creation via Blueprint is safe.
        Schema::create('emergency_cases', function (Blueprint $table) {
            $table->id();
            $table->string('emergency_number')->unique();

            $table->foreignId('visit_id')->unique()
                  ->constrained('visits')->cascadeOnDelete();
            $table->foreignId('patient_id')
                  ->constrained('patients')->cascadeOnDelete();

            $table->foreignId('registered_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            // Arrival information
            $table->string('arrival_mode', 32)->default('walk_in'); // EmergencyArrivalMode
            $table->dateTime('arrival_time')->nullable();
            $table->string('brought_by')->nullable();
            $table->string('accompanied_by')->nullable();
            $table->string('referral_source')->nullable();
            $table->text('chief_complaint')->nullable();

            // Triage
            $table->string('triage_category', 16)->nullable(); // EmergencyTriageCategory
            $table->dateTime('triaged_at')->nullable();
            $table->foreignId('triaged_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            // Treatment area / assignments
            $table->foreignId('treatment_area_id')->nullable()
                  ->constrained('departments')->nullOnDelete();
            $table->foreignId('assigned_doctor_id')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_nurse_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            // Case lifecycle
            $table->string('status', 32)->default('registered'); // EmergencyCaseStatus

            // Disposition
            $table->string('disposition', 48)->nullable(); // EmergencyDisposition
            $table->dateTime('disposition_at')->nullable();
            $table->foreignId('disposition_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->text('discharge_summary')->nullable();
            $table->string('referral_facility')->nullable();
            $table->text('referral_reason')->nullable();

            // Death certification
            $table->dateTime('death_time')->nullable();
            $table->string('death_cause')->nullable();
            $table->foreignId('certified_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('triage_category');
            $table->index('arrival_time');
            $table->index('assigned_doctor_id');
            $table->index('assigned_nurse_id');
        });

        // Idempotently add emergency_case_id to admissions, using raw DB::statement
        // (avoids the MariaDB Schema::table generation_expression bug).
        $hasCol = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.columns
              WHERE table_schema = DATABASE()
                AND table_name = 'admissions'
                AND column_name = 'emergency_case_id'"
        );
        if (! $hasCol || (int) $hasCol->c === 0) {
            DB::statement('ALTER TABLE `admissions`
                ADD COLUMN `emergency_case_id` BIGINT UNSIGNED NULL AFTER `visit_id`');
        }

        $hasIdx = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name = 'admissions'
                AND index_name = 'admissions_emergency_case_id_index'"
        );
        if (! $hasIdx || (int) $hasIdx->c === 0) {
            DB::statement('CREATE INDEX `admissions_emergency_case_id_index`
                ON `admissions` (`emergency_case_id`)');
        }
    }

    public function down(): void
    {
        // Drop FK-less index + column from admissions first
        $hasIdx = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name = 'admissions'
                AND index_name = 'admissions_emergency_case_id_index'"
        );
        if ($hasIdx && (int) $hasIdx->c > 0) {
            DB::statement('DROP INDEX `admissions_emergency_case_id_index` ON `admissions`');
        }

        $hasCol = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.columns
              WHERE table_schema = DATABASE()
                AND table_name = 'admissions'
                AND column_name = 'emergency_case_id'"
        );
        if ($hasCol && (int) $hasCol->c > 0) {
            DB::statement('ALTER TABLE `admissions` DROP COLUMN `emergency_case_id`');
        }

        Schema::dropIfExists('emergency_cases');
    }
};

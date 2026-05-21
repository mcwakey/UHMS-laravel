<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patient_number_sequences')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::create('patient_number_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('prefix', 20);
                $table->string('period_type', 10)->default('yearly'); // never|yearly|monthly|daily
                $table->string('period_key', 20)->default('GLOBAL');
                $table->unsignedBigInteger('last_sequence')->default(0);
                $table->timestamps();
                $table->unique(['prefix', 'period_type', 'period_key'], 'pns_unique');
            });
            return;
        }

        // MariaDB: raw DDL
        DB::statement("
            CREATE TABLE `patient_number_sequences` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `prefix` VARCHAR(20) NOT NULL,
                `period_type` ENUM('never','yearly','monthly','daily') NOT NULL DEFAULT 'yearly',
                `period_key` VARCHAR(20) NOT NULL DEFAULT 'GLOBAL',
                `last_sequence` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                UNIQUE KEY `pns_unique` (`prefix`, `period_type`, `period_key`)
            ) ENGINE=InnoDB
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_number_sequences');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addClinicalContextColumns('complaints');
        $this->addClinicalContextColumns('diagnoses');
        $this->addClinicalContextColumns('investigations');
        $this->addClinicalContextColumns('treatments');
        $this->addClinicalContextColumns('prescriptions');
        $this->addClinicalContextColumns('consultation_tasks', includeUpdatedBy: false, includeDoctor: false);
        $this->addTaskCompletionColumns();
        $this->backfillExistingClinicalContext();

        if (! Schema::hasTable('history_of_presenting_complaints')) Schema::create('history_of_presenting_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes')->nullOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('content');
            $table->string('onset')->nullable();
            $table->string('duration')->nullable();
            $table->string('location')->nullable();
            $table->string('character')->nullable();
            $table->string('radiation')->nullable();
            $table->text('associated_symptoms')->nullable();
            $table->text('aggravating_factors')->nullable();
            $table->text('relieving_factors')->nullable();
            $table->string('severity')->nullable();
            $table->string('timing')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('source_pattern_id')->nullable()->constrained('medical_patterns')->nullOnDelete();
            $table->timestamps();

            $table->index(['medical_record_id', 'created_at']);
            $table->index(['consultation_route_id', 'created_at']);
        });

        if (! Schema::hasTable('physical_examinations')) Schema::create('physical_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes')->nullOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('findings');
            $table->text('general_examination')->nullable();
            $table->text('systemic_examination')->nullable();
            $table->text('cardiovascular')->nullable();
            $table->text('respiratory')->nullable();
            $table->text('gastrointestinal')->nullable();
            $table->text('central_nervous_system')->nullable();
            $table->text('musculoskeletal')->nullable();
            $table->text('specialty_examination')->nullable();
            $table->text('local_examination')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('source_pattern_id')->nullable()->constrained('medical_patterns')->nullOnDelete();
            $table->timestamps();

            $table->index(['medical_record_id', 'created_at']);
            $table->index(['consultation_route_id', 'created_at']);
        });

        if (! Schema::hasTable('consultation_session_contributors')) Schema::create('consultation_session_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_route_id')->constrained('visit_consultation_routes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamp('first_contributed_at')->nullable();
            $table->timestamp('last_contributed_at')->nullable();
            $table->timestamps();

            $table->unique(['consultation_route_id', 'user_id'], 'consult_route_user_unique');
        });

        if (! Schema::hasTable('medical_record_entry_logs')) Schema::create('medical_record_entry_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type');
            $table->unsignedBigInteger('entry_id');
            $table->string('action');
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entry_type', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_entry_logs');
        Schema::dropIfExists('consultation_session_contributors');
        Schema::dropIfExists('physical_examinations');
        Schema::dropIfExists('history_of_presenting_complaints');
    }

    private function addClinicalContextColumns(string $tableName, bool $includeUpdatedBy = true, bool $includeDoctor = true): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $driver = DB::getDriverName();

        // Build ordered column definitions
        $columns = [
            'consultation_route_id' => ['ref' => 'visit_consultation_routes', 'delete' => 'SET NULL'],
            'visit_id'              => ['ref' => 'visits',                    'delete' => 'CASCADE'],
            'patient_id'            => ['ref' => 'patients',                  'delete' => 'CASCADE'],
            'department_id'         => ['ref' => 'departments',               'delete' => 'SET NULL'],
        ];
        if ($includeDoctor) {
            $columns['doctor_id'] = ['ref' => 'users', 'delete' => 'SET NULL'];
        }
        $columns['created_by'] = ['ref' => 'users', 'delete' => 'SET NULL'];
        if ($includeUpdatedBy) {
            $columns['updated_by'] = ['ref' => 'users', 'delete' => 'SET NULL'];
        }
        $columns['source_pattern_id'] = ['ref' => 'medical_patterns', 'delete' => 'SET NULL'];

        foreach ($columns as $col => $def) {
            $exists = $driver === 'sqlite'
                ? Schema::hasColumn($tableName, $col)
                : $this->mysqlHasColumn($tableName, $col);

            if ($exists) {
                continue;
            }

            if ($driver === 'sqlite') {
                Schema::table($tableName, function (Blueprint $table) use ($col, $def) {
                    $fk = $table->foreignId($col)->nullable()->constrained($def['ref']);
                    $def['delete'] === 'CASCADE' ? $fk->cascadeOnDelete() : $fk->nullOnDelete();
                });
            } else {
                DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `{$col}` BIGINT UNSIGNED NULL DEFAULT NULL");
                DB::statement(
                    "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$tableName}_{$col}_foreign`
                     FOREIGN KEY (`{$col}`) REFERENCES `{$def['ref']}` (`id`) ON DELETE {$def['delete']}"
                );
            }
        }
    }

    private function addTaskCompletionColumns(): void
    {
        if (! Schema::hasTable('consultation_tasks')) {
            return;
        }

        $driver   = DB::getDriverName();
        $exists   = $driver === 'sqlite'
            ? Schema::hasColumn('consultation_tasks', 'completed_by')
            : $this->mysqlHasColumn('consultation_tasks', 'completed_by');

        if ($exists) {
            return;
        }

        if ($driver === 'sqlite') {
            Schema::table('consultation_tasks', function (Blueprint $table) {
                $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            });
        } else {
            DB::statement("ALTER TABLE `consultation_tasks` ADD COLUMN `completed_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `completed_at`");
            DB::statement(
                "ALTER TABLE `consultation_tasks` ADD CONSTRAINT `consultation_tasks_completed_by_foreign`
                 FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL"
            );
        }
    }

    private function backfillExistingClinicalContext(): void
    {
        $driver = DB::getDriverName();

        foreach (['complaints', 'diagnoses', 'investigations', 'treatments', 'prescriptions', 'consultation_tasks'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $hasMrId = $driver === 'sqlite'
                ? Schema::hasColumn($tableName, 'medical_record_id')
                : $this->mysqlHasColumn($tableName, 'medical_record_id');

            if (! $hasMrId) {
                continue;
            }

            $hasDoctorId  = $driver === 'sqlite'
                ? Schema::hasColumn($tableName, 'doctor_id')
                : $this->mysqlHasColumn($tableName, 'doctor_id');
            $hasCreatedBy = $driver === 'sqlite'
                ? Schema::hasColumn($tableName, 'created_by')
                : $this->mysqlHasColumn($tableName, 'created_by');

            DB::table($tableName)
                ->join('medical_records', "{$tableName}.medical_record_id", '=', 'medical_records.id')
                ->select(
                    "{$tableName}.id",
                    'medical_records.visit_id',
                    'medical_records.patient_id',
                    'medical_records.department_id',
                    'medical_records.consultation_route_id',
                    'medical_records.doctor_id',
                )
                ->orderBy("{$tableName}.id")
                ->chunk(200, function ($rows) use ($tableName, $hasDoctorId, $hasCreatedBy) {
                    foreach ($rows as $row) {
                        $updates = [
                            'visit_id'               => $row->visit_id,
                            'patient_id'             => $row->patient_id,
                            'department_id'          => $row->department_id,
                            'consultation_route_id'  => $row->consultation_route_id,
                        ];

                        if ($hasDoctorId) {
                            $updates['doctor_id'] = $row->doctor_id;
                        }
                        if ($tableName !== 'consultation_tasks' && $hasCreatedBy) {
                            $updates['created_by'] = $row->doctor_id;
                        }

                        DB::table($tableName)->where('id', $row->id)->update($updates);
                    }
                });
        }
    }

    /** Raw column-existence check safe for MariaDB 10.1.x (no generation_expression). */
    private function mysqlHasColumn(string $table, string $column): bool
    {
        $result = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }
};

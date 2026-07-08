<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addAdmissionPlanningColumns();

        if (Schema::hasTable('admission_discharge_clearances')) {
            $this->repairClearancesTable();
        } else {
            $this->createClearancesTable();
        }

        if (Schema::hasTable('admission_discharge_summaries')) {
            $this->repairSummariesTable();

            return;
        }

        Schema::create('admission_discharge_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('primary_diagnosis')->nullable();
            $table->json('secondary_diagnoses')->nullable();
            $table->text('admission_reason')->nullable();
            $table->text('hospital_course')->nullable();
            $table->text('investigations_summary')->nullable();
            $table->text('procedures_summary')->nullable();
            $table->text('treatment_given')->nullable();
            $table->string('discharge_condition')->nullable();
            $table->text('discharge_medications')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('warning_signs')->nullable();
            $table->string('final_outcome')->nullable();
            $table->string('summary_status')->default('draft');
            $table->timestamps();

            $table->unique('admission_id', 'adm_discharge_summary_admission_unique');
            $table->index(['summary_status', 'approved_at'], 'adm_discharge_summary_status_approved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_discharge_summaries');
        Schema::dropIfExists('admission_discharge_clearances');

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discharge_planning_started_by');
            $table->dropColumn([
                'discharge_planning_started_at',
                'expected_discharge_at',
                'discharge_planning_note',
            ]);
        });
    }

    private function addAdmissionPlanningColumns(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            if (! Schema::hasColumn('admissions', 'discharge_planning_started_at')) {
                $table->timestamp('discharge_planning_started_at')->nullable()->after('care_flags');
            }

            if (! Schema::hasColumn('admissions', 'discharge_planning_started_by')) {
                $table->foreignId('discharge_planning_started_by')->nullable()->after('discharge_planning_started_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('admissions', 'expected_discharge_at')) {
                $table->timestamp('expected_discharge_at')->nullable()->after('discharge_planning_started_by');
            }

            if (! Schema::hasColumn('admissions', 'discharge_planning_note')) {
                $table->text('discharge_planning_note')->nullable()->after('expected_discharge_at');
            }
        });
    }

    private function createClearancesTable(): void
    {
        Schema::create('admission_discharge_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('clearance_type');
            $table->string('status')->default('pending');
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['admission_id', 'clearance_type'], 'adm_discharge_clear_admission_type_unique');
            $table->index(['admission_id', 'status'], 'adm_discharge_clear_admission_status_idx');
            $table->index('clearance_type', 'adm_discharge_clear_type_idx');
        });
    }

    private function repairClearancesTable(): void
    {
        if (! $this->hasIndex('admission_discharge_clearances', 'adm_discharge_clear_admission_type_unique')) {
            Schema::table('admission_discharge_clearances', function (Blueprint $table) {
                $table->unique(['admission_id', 'clearance_type'], 'adm_discharge_clear_admission_type_unique');
            });
        }

        if (! $this->hasIndex('admission_discharge_clearances', 'adm_discharge_clear_admission_status_idx')) {
            Schema::table('admission_discharge_clearances', function (Blueprint $table) {
                $table->index(['admission_id', 'status'], 'adm_discharge_clear_admission_status_idx');
            });
        }

        if (! $this->hasIndex('admission_discharge_clearances', 'adm_discharge_clear_type_idx')) {
            Schema::table('admission_discharge_clearances', function (Blueprint $table) {
                $table->index('clearance_type', 'adm_discharge_clear_type_idx');
            });
        }
    }

    private function repairSummariesTable(): void
    {
        if (! $this->hasIndex('admission_discharge_summaries', 'adm_discharge_summary_admission_unique')) {
            Schema::table('admission_discharge_summaries', function (Blueprint $table) {
                $table->unique('admission_id', 'adm_discharge_summary_admission_unique');
            });
        }

        if (! $this->hasIndex('admission_discharge_summaries', 'adm_discharge_summary_status_approved_idx')) {
            Schema::table('admission_discharge_summaries', function (Blueprint $table) {
                $table->index(['summary_status', 'approved_at'], 'adm_discharge_summary_status_approved_idx');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA index_list('.$table.')'))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        return (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index],
        )->aggregate ?? 0) > 0;
    }
};

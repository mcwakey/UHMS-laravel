<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_merge_requests')) {
            Schema::create('patient_merge_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 40)->unique();
                $table->foreignId('main_patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('duplicate_patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('PENDING_REVIEW');
                $table->text('reason')->nullable();
                $table->unsignedTinyInteger('match_confidence')->nullable();
                $table->longText('field_resolution')->nullable();
                $table->longText('preview_summary')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['main_patient_id', 'status'], 'pmr_main_status_idx');
                $table->index(['duplicate_patient_id', 'status'], 'pmr_dup_status_idx');
                $table->index(['status', 'created_at'], 'pmr_status_created_idx');
            });
        }

        if (! Schema::hasTable('patient_merge_logs')) {
            Schema::create('patient_merge_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_merge_request_id')->nullable()->constrained('patient_merge_requests')->nullOnDelete();
                $table->foreignId('main_patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('duplicate_patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->string('action', 80);
                $table->string('table_name', 100)->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->longText('old_values')->nullable();
                $table->longText('new_values')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->index(['patient_merge_request_id', 'occurred_at'], 'pml_request_time_idx');
                $table->index(['main_patient_id', 'occurred_at'], 'pml_main_time_idx');
                $table->index(['duplicate_patient_id', 'occurred_at'], 'pml_dup_time_idx');
            });
        }

        if (! Schema::hasTable('patient_aliases')) {
            Schema::create('patient_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('source_patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->string('alias_type', 50);
                $table->string('alias_value', 191);
                $table->string('normalized_alias_value', 120);
                $table->longText('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['alias_type', 'normalized_alias_value'], 'patient_aliases_alias_unique');
                $table->index(['patient_id', 'alias_type'], 'patient_aliases_patient_type_idx');
                $table->index('source_patient_id', 'patient_aliases_source_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_aliases');
        Schema::dropIfExists('patient_merge_logs');
        Schema::dropIfExists('patient_merge_requests');
    }
};

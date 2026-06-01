<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->columnExists('service_catalog', 'requires_rendering_tracking')) {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->boolean('requires_rendering_tracking')
                    ->nullable()
                    ->after('is_billable');
            });
        }

        if (! Schema::hasTable('service_renderings')) {
            Schema::create('service_renderings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('invoice_item_id')->unique()->constrained('invoice_items')->cascadeOnDelete();
                $table->foreignId('service_id')->constrained('service_catalog')->restrictOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('emergency_case_id')->nullable()->constrained('emergency_cases')->nullOnDelete();
                $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
                $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes')->nullOnDelete();
                $table->foreignId('rendered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('rendered_at')->nullable();
                $table->string('status', 32)->default('PENDING');
                $table->text('notes')->nullable();
                $table->text('result_summary')->nullable();
                $table->text('reason_not_rendered')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['department_id', 'status']);
                $table->index(['visit_id', 'status']);
                $table->index(['patient_id', 'status']);
                $table->index(['rendered_by', 'rendered_at']);
                $table->index(['emergency_case_id', 'status']);
                $table->index(['admission_id', 'status']);
            });
        }

        if (! Schema::hasTable('service_rendering_logs')) {
            Schema::create('service_rendering_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_rendering_id')->constrained('service_renderings')->cascadeOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();
                $table->string('action', 64);
                $table->text('notes')->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['service_rendering_id', 'created_at'], 'sr_logs_rendering_created_idx');
                $table->index(['visit_id', 'created_at'], 'sr_logs_visit_created_idx');
                $table->index(['action', 'created_at'], 'sr_logs_action_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rendering_logs');
        Schema::dropIfExists('service_renderings');

        if ($this->columnExists('service_catalog', 'requires_rendering_tracking')) {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->dropColumn('requires_rendering_tracking');
            });
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        try {
            $database = DB::connection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
                [$database, $table, $column],
            );

            return (int) ($row->c ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }
};

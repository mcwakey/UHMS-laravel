<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existing = $this->existingColumns();

        Schema::table('visit_services', function (Blueprint $table) use ($existing) {
            if (! in_array('patient_insurance_id', $existing, true)) {
                $table->foreignId('patient_insurance_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('patient_insurances')
                    ->nullOnDelete();
            }

            if (! in_array('payment_type', $existing, true)) {
                $table->string('payment_type', 20)->nullable()->after('patient_insurance_id');
            }

            if (! in_array('insurance_type', $existing, true)) {
                $table->string('insurance_type', 20)->nullable()->after('payment_type');
            }

            if (! in_array('pricing_source', $existing, true)) {
                $table->string('pricing_source', 40)->nullable()->after('insurance_type');
            }
        });
    }

    public function down(): void
    {
        $existing = $this->existingColumns();

        Schema::table('visit_services', function (Blueprint $table) use ($existing) {
            if (in_array('pricing_source', $existing, true)) {
                $table->dropColumn('pricing_source');
            }
            if (in_array('insurance_type', $existing, true)) {
                $table->dropColumn('insurance_type');
            }
            if (in_array('payment_type', $existing, true)) {
                $table->dropColumn('payment_type');
            }
            if (in_array('patient_insurance_id', $existing, true)) {
                try {
                    $table->dropForeign(['patient_insurance_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('patient_insurance_id');
            }
        });
    }

    private function existingColumns(): array
    {
        try {
            $rows = DB::select('SHOW COLUMNS FROM visit_services');

            return array_map(fn ($r) => $r->Field, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
};

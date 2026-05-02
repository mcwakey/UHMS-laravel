<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropColumn([
                'coverage_percentage',
                'per_visit_limit',
                'annual_limit',
                'max_per_month',
                'max_visits_per_month',
            ]);
        });

        if (Schema::hasColumn('insurance_providers', 'tier')) {
            Schema::table('insurance_providers', function (Blueprint $table) {
                $table->dropColumn('tier');
            });
        }
    }

    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->decimal('coverage_percentage', 5, 2)->default(100)->after('contract_number');
            $table->decimal('per_visit_limit', 12, 2)->nullable()->after('coverage_percentage');
            $table->decimal('annual_limit', 12, 2)->nullable()->after('per_visit_limit');
            $table->decimal('max_per_month', 12, 2)->nullable()->after('annual_limit');
            $table->unsignedSmallInteger('max_visits_per_month')->nullable()->after('max_per_month');
            $table->string('tier')->nullable()->after('max_visits_per_month');
        });
    }
};

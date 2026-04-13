<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->decimal('annual_limit', 12, 2)->nullable()->after('is_active');
            $table->decimal('per_visit_limit', 12, 2)->nullable()->after('annual_limit');
            $table->string('tier')->nullable()->after('per_visit_limit');
            $table->decimal('coverage_percentage', 5, 2)->default(100)->after('tier');
            $table->boolean('is_default')->default(false)->after('coverage_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropColumn(['annual_limit', 'per_visit_limit', 'tier', 'coverage_percentage', 'is_default']);
        });
    }
};

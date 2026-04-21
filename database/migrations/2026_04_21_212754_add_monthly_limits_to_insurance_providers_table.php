<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->decimal('max_per_month', 12, 2)->nullable()->after('per_visit_limit');
            $table->unsignedSmallInteger('max_visits_per_month')->nullable()->after('max_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropColumn(['max_per_month', 'max_visits_per_month']);
        });
    }
};

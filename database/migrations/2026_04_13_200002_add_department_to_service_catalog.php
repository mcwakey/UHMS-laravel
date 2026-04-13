<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add department_id to service_catalog for Department → Services relationship
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('is_active')
                ->constrained()->nullOnDelete();
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'description']);
        });
    }
};

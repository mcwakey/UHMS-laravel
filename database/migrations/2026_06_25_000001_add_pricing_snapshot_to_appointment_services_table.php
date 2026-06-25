<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            if (! Schema::hasColumn('appointment_services', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('appointment_services', 'total_price')) {
                $table->decimal('total_price', 10, 2)->nullable()->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_services', 'total_price')) {
                $table->dropColumn('total_price');
            }

            if (Schema::hasColumn('appointment_services', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });
    }
};

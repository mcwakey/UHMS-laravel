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
        if (! Schema::hasColumn('patients', 'nhis_number') && ! Schema::hasColumn('patients', 'nhis_expiry_date')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            try {
                $table->dropIndex('patients_nhis_number_index');
            } catch (\Throwable) {
                // SQLite test migrations can reach this point without the index metadata being present.
            }

            $table->dropColumn(['nhis_number', 'nhis_expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('nhis_number', 30)->nullable()->after('ghana_card_number');
            $table->date('nhis_expiry_date')->nullable()->after('nhis_number');
            $table->index('nhis_number');
        });
    }
};

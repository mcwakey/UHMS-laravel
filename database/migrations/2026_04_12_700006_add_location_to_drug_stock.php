<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drug_stock', function (Blueprint $table) {
            $table->string('location')->default('pharmacy')->after('drug_id');
            $table->foreignId('supplier_id')->nullable()->after('supplier')->constrained('suppliers')->nullOnDelete();

            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::table('drug_stock', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn('location');
        });
    }
};

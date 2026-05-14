<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('drug_generic_names')) {
            Schema::create('drug_generic_names', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191)->unique();
                $table->string('atc_code', 30)->nullable();
                $table->string('therapeutic_class', 191)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Add generic_name_id to drugs
        if (Schema::hasTable('drugs')) {
            $exists = DB::select("SHOW COLUMNS FROM drugs LIKE 'generic_name_id'");
            if (empty($exists)) {
                Schema::table('drugs', function (Blueprint $table) {
                    $table->unsignedBigInteger('generic_name_id')->nullable()->after('product_id');
                    $table->foreign('generic_name_id')->references('id')->on('drug_generic_names')->nullOnDelete();
                });
            }
        }

        // Add generic_name_id to products (optional, useful for non-drug products too)
        if (Schema::hasTable('products')) {
            $exists = DB::select("SHOW COLUMNS FROM products LIKE 'generic_name_id'");
            if (empty($exists)) {
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedBigInteger('generic_name_id')->nullable()->after('id');
                    $table->foreign('generic_name_id')->references('id')->on('drug_generic_names')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drugs')) {
            $exists = DB::select("SHOW COLUMNS FROM drugs LIKE 'generic_name_id'");
            if (!empty($exists)) {
                Schema::table('drugs', function (Blueprint $t) {
                    try { $t->dropForeign(['generic_name_id']); } catch (\Throwable $e) {}
                    $t->dropColumn('generic_name_id');
                });
            }
        }
        if (Schema::hasTable('products')) {
            $exists = DB::select("SHOW COLUMNS FROM products LIKE 'generic_name_id'");
            if (!empty($exists)) {
                Schema::table('products', function (Blueprint $t) {
                    try { $t->dropForeign(['generic_name_id']); } catch (\Throwable $e) {}
                    $t->dropColumn('generic_name_id');
                });
            }
        }
        Schema::dropIfExists('drug_generic_names');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $has = collect(DB::select("SHOW COLUMNS FROM drugs LIKE 'product_id'"))->isNotEmpty();
        if (! $has) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        $has = collect(DB::select("SHOW COLUMNS FROM drugs LIKE 'product_id'"))->isNotEmpty();
        if ($has) {
            Schema::table('drugs', function (Blueprint $table) {
                try { $table->dropForeign(['product_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['product_id']); }   catch (\Throwable $e) {}
                $table->dropColumn('product_id');
            });
        }
    }
};

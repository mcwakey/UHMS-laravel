<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $existing = DB::getDriverName() === 'sqlite'
            ? Schema::getColumnListing('lab_request_items')
            : collect(DB::select('SHOW COLUMNS FROM lab_request_items'))->pluck('Field')->all();

        Schema::table('lab_request_items', function (Blueprint $table) use ($existing) {
            if (!in_array('accepted_at', $existing)) {
                $table->timestamp('accepted_at')->nullable()->after('status');
            }
            if (!in_array('accepted_by', $existing)) {
                $table->unsignedBigInteger('accepted_by')->nullable()->after('accepted_at');
            }
            if (!in_array('billed_at', $existing)) {
                $table->timestamp('billed_at')->nullable()->after('accepted_by');
            }
            if (!in_array('invoice_item_id', $existing)) {
                $table->unsignedBigInteger('invoice_item_id')->nullable()->after('billed_at');
            }
            if (!in_array('unit_price', $existing)) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('invoice_item_id');
            }
        });

        // Add FKs only if columns are new
        Schema::table('lab_request_items', function (Blueprint $table) {
            try { $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete(); } catch (\Throwable $e) {}
            try { $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->nullOnDelete(); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table) {
            try { $table->dropForeign(['accepted_by']); } catch (\Throwable $e) {}
            try { $table->dropForeign(['invoice_item_id']); } catch (\Throwable $e) {}
            $table->dropColumn(['accepted_at', 'accepted_by', 'billed_at', 'invoice_item_id', 'unit_price']);
        });
    }
};

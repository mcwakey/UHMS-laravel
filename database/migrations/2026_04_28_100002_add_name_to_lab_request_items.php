<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table) {
            // For non-parameter requests the item has a free-text name (e.g. "Chest X-Ray")
            $table->string('name')->nullable()->after('lab_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};

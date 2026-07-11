<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front Desk lost & found register (Phase 18E). Items found / reported lost in
 * the facility. Non-clinical; phone numbers are masked in lists/exports. No
 * patient link in this phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_desk_lost_found_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable()->unique();
            // found | reported_lost | claimed | released | disposed | cancelled
            $table->string('item_status')->default('found')->index();
            // App\Enums\FrontDesk\LostFoundCategory
            $table->string('item_category')->default('other')->index();
            $table->text('item_description');
            $table->dateTime('found_or_reported_at');
            $table->string('found_location')->nullable();
            $table->string('found_by_name')->nullable();
            $table->string('reported_by_name')->nullable();
            $table->string('reported_by_phone')->nullable();
            $table->string('stored_location')->nullable();
            $table->string('claimed_by_name')->nullable();
            $table->string('claimed_by_phone')->nullable();
            $table->foreignId('claim_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->text('disposal_note')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('found_or_reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_lost_found_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_specialty_order_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')->constrained('consultation_specialty_profiles')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['consultation_specialty_profile_id', 'code'], 'cso_sets_profile_code_unique');
            $table->index('consultation_specialty_profile_id', 'cso_sets_profile_idx');
            $table->index('category', 'cso_sets_category_idx');
            $table->index('is_active', 'cso_sets_active_idx');
            $table->index('sort_order', 'cso_sets_sort_idx');
        });

        Schema::create('consultation_specialty_order_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_order_set_id')->constrained('consultation_specialty_order_sets')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('target_section')->nullable();
            $table->string('target_field')->nullable();
            $table->nullableMorphs('favoritable', 'cso_items_favoritable');
            $table->string('code')->nullable();
            $table->json('payload')->nullable();
            $table->string('apply_mode')->default('suggest');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('item_type', 'cso_items_type_idx');
            $table->index('apply_mode', 'cso_items_apply_mode_idx');
            $table->index('is_active', 'cso_items_active_idx');
            $table->index('sort_order', 'cso_items_sort_idx');
        });

        Schema::create('consultation_specialty_order_set_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('visit_consultation_routes')->cascadeOnDelete();
            $table->foreignId('consultation_specialty_order_set_id')->nullable()->constrained('consultation_specialty_order_sets')->nullOnDelete();
            $table->foreignId('consultation_specialty_profile_id')->nullable()->constrained('consultation_specialty_profiles')->nullOnDelete();
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('applied');
            $table->json('preview_payload')->nullable();
            $table->json('applied_payload')->nullable();
            $table->json('warnings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('consultation_id', 'cso_apps_consultation_idx');
            $table->index('status', 'cso_apps_status_idx');
        });

        Schema::create('consultation_specialty_order_set_application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_order_set_application_id')->constrained('consultation_specialty_order_set_applications')->cascadeOnDelete();
            $table->foreignId('consultation_specialty_order_set_item_id')->nullable()->constrained('consultation_specialty_order_set_items')->nullOnDelete();
            $table->string('item_type');
            $table->string('label');
            $table->string('apply_mode');
            $table->string('status');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('payload')->nullable();
            $table->text('message')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamps();

            $table->index('status', 'cso_app_items_status_idx');
            $table->index(['target_type', 'target_id'], 'cso_app_items_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_specialty_order_set_application_items');
        Schema::dropIfExists('consultation_specialty_order_set_applications');
        Schema::dropIfExists('consultation_specialty_order_set_items');
        Schema::dropIfExists('consultation_specialty_order_sets');
    }
};

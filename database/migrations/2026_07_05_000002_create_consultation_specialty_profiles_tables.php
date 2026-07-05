<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_specialty_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_type')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('consultation_specialty_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles')
                ->cascadeOnDelete();
            $table->string('section_key');
            $table->string('label');
            $table->string('component')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['consultation_specialty_profile_id', 'section_key'], 'consult_specialty_section_profile_key_unique');
            $table->index(['consultation_specialty_profile_id', 'display_order'], 'consult_specialty_section_profile_order_idx');
        });

        Schema::create('consultation_specialty_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('schema')->nullable();
            $table->json('summary_mapper')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('consultation_specialty_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')
                ->nullable()
                ->constrained('visit_consultation_routes')
                ->nullOnDelete();
            $table->foreignId('consultation_specialty_profile_id')
                ->nullable()
                ->constrained('consultation_specialty_profiles')
                ->nullOnDelete();
            $table->string('section_key');
            $table->json('entry')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consultation_id', 'consultation_specialty_profile_id'], 'consult_specialty_entries_consult_profile_idx');
            $table->index(['section_key'], 'consult_specialty_entries_section_key_idx');
        });

        Schema::create('doctor_consultation_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('default_consultation_specialty_profile_id')
                ->nullable()
                ->constrained('consultation_specialty_profiles')
                ->nullOnDelete();
            $table->foreignId('default_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->json('pinned_actions')->nullable();
            $table->string('preferred_layout')->nullable();
            $table->boolean('compact_mode')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_consultation_preferences');
        Schema::dropIfExists('consultation_specialty_entries');
        Schema::dropIfExists('consultation_specialty_templates');
        Schema::dropIfExists('consultation_specialty_sections');
        Schema::dropIfExists('consultation_specialty_profiles');
    }
};

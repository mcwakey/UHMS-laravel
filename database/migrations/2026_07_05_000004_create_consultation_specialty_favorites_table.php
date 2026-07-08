<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_specialty_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles', indexName: 'csf_profile_fk')
                ->cascadeOnDelete();
            $table->string('favorite_type');
            $table->nullableMorphs('favoritable', 'csf_favoritable_idx');
            $table->string('code')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->text('search_terms')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('consultation_specialty_profile_id', 'csf_profile_idx');
            $table->index('favorite_type', 'csf_type_idx');
            $table->index('is_active', 'csf_active_idx');
            $table->index('sort_order', 'csf_sort_idx');
            $table->unique(
                ['consultation_specialty_profile_id', 'favorite_type', 'code'],
                'csf_profile_type_code_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_specialty_favorites');
    }
};

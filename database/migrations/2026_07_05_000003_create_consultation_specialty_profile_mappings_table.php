<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_specialty_profile_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles', indexName: 'cspm_profile_fk')
                ->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes', indexName: 'cspm_route_fk')->nullOnDelete();
            $table->string('department_type')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('consultation_specialty_profile_id', 'consult_specialty_mapping_profile_idx');
            $table->index('department_id', 'consult_specialty_mapping_department_idx');
            $table->index('consultation_route_id', 'consult_specialty_mapping_route_idx');
            $table->index('department_type', 'consult_specialty_mapping_dept_type_idx');
            $table->index('is_active', 'consult_specialty_mapping_active_idx');
            $table->index('priority', 'consult_specialty_mapping_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_specialty_profile_mappings');
    }
};

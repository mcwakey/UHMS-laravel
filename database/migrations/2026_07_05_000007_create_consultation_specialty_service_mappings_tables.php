<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_specialty_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')->constrained('consultation_specialty_profiles')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedBigInteger('consultation_route_id')->nullable()->index('cssm_route_idx');
            $table->string('department_type')->nullable()->index('cssm_department_type_idx');
            $table->string('section_key')->nullable();
            $table->string('mapping_context')->default('consultation')->index('cssm_context_idx');
            $table->string('billing_trigger')->default('manual')->index('cssm_trigger_idx');
            $table->unsignedInteger('priority')->default(0)->index('cssm_priority_idx');
            $table->boolean('is_default')->default(false)->index('cssm_default_idx');
            $table->boolean('auto_bill')->default(false)->index('cssm_auto_bill_idx');
            $table->boolean('requires_confirmation')->default(true);
            $table->boolean('is_active')->default(true)->index('cssm_active_idx');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['consultation_specialty_profile_id', 'mapping_context'], 'cssm_profile_context_idx');
            $table->index(['department_id', 'mapping_context'], 'cssm_department_context_idx');
        });

        Schema::create('consultation_specialty_billing_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('visit_consultation_routes')->cascadeOnDelete();
            $table->foreignId('consultation_specialty_profile_id')->nullable()->constrained('consultation_specialty_profiles')->nullOnDelete();
            $table->foreignId('consultation_specialty_service_mapping_id')->nullable()->constrained('consultation_specialty_service_mappings')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable()->index('csba_invoice_idx');
            $table->unsignedBigInteger('invoice_item_id')->nullable()->index('csba_invoice_item_idx');
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('previewed')->index('csba_status_idx');
            $table->string('trigger')->nullable();
            $table->json('preview_payload')->nullable();
            $table->json('applied_payload')->nullable();
            $table->json('warnings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['consultation_id', 'consultation_specialty_service_mapping_id'], 'csba_consult_mapping_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_specialty_billing_applications');
        Schema::dropIfExists('consultation_specialty_service_mappings');
    }
};

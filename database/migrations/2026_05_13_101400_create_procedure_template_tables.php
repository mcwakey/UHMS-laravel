<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procedure_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->string('template_type', 30);            // PRE_OP, ANAESTHESIA, OPERATIVE_NOTE, POST_OP, FULL_REPORT
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_id', 'template_type', 'sort_order'], 'pts_svc_type_sort_idx');
        });

        Schema::create('procedure_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('procedure_template_sections')->nullOnDelete();
            $table->string('template_type', 30);
            $table->string('label');
            $table->string('field_key', 100);
            $table->string('input_type', 30)->default('text');   // text, textarea, number, select, checkbox, date, time, datetime, file
            $table->text('options')->nullable();                 // JSON for select options
            $table->string('default_value', 500)->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_id', 'template_type', 'sort_order'], 'ptf_svc_type_sort_idx');
            $table->index('section_id');
        });

        Schema::create('procedure_template_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_request_id')->constrained('procedure_requests')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->foreignId('template_field_id')->nullable()->constrained('procedure_template_fields')->nullOnDelete();
            $table->string('template_type', 30);
            $table->string('field_key', 100);              // snapshot
            $table->string('field_label')->nullable();     // snapshot
            $table->string('input_type', 30)->nullable();  // snapshot
            $table->text('value')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['procedure_request_id', 'template_type'], 'ptv_req_type_idx');
            $table->index('template_field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_template_values');
        Schema::dropIfExists('procedure_template_fields');
        Schema::dropIfExists('procedure_template_sections');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('visit_type');       // VisitType enum
            $table->date('visit_date');
            $table->string('status')->default('registered'); // VisitStatus enum
            $table->string('priority')->default('normal');   // Priority enum
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('visit_date');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};

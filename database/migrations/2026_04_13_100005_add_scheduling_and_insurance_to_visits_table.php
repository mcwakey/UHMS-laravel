<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Scheduling fields (appointments merged into visits)
            $table->time('start_time')->nullable()->after('visit_date');
            $table->time('end_time')->nullable()->after('start_time');

            // Per-visit insurance selection
            $table->foreignId('visit_insurance_id')->nullable()->after('created_by')
                ->constrained('patient_insurances')->nullOnDelete();

            // Cancellation tracking
            $table->foreignId('cancelled_by')->nullable()->after('visit_insurance_id')
                ->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');

            // Reschedule tracking
            $table->foreignId('rescheduled_from_id')->nullable()->after('cancellation_reason');
            $table->foreign('rescheduled_from_id')->references('id')->on('visits')->nullOnDelete();
            $table->timestamp('rescheduled_at')->nullable()->after('rescheduled_from_id');
            $table->text('rescheduled_reason')->nullable()->after('rescheduled_at');

            // Consultation mode
            $table->string('consultation_mode')->default('in_person')->after('rescheduled_reason');
            $table->string('meeting_link')->nullable()->after('consultation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['visit_insurance_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['rescheduled_from_id']);
            $table->dropColumn([
                'start_time', 'end_time', 'visit_insurance_id',
                'cancelled_by', 'cancellation_reason',
                'rescheduled_from_id', 'rescheduled_at', 'rescheduled_reason',
                'consultation_mode', 'meeting_link',
            ]);
        });
    }
};

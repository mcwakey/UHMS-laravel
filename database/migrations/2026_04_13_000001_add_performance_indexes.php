<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 21: Performance optimization — add missing indexes on frequently queried columns.
     */
    public function up(): void
    {
        // Invoices — status, billing_type, due_date
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('status');
            $table->index('billing_type');
            $table->index('due_date');
        });

        // Payments — payment_method, paid_at
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_method');
            $table->index('paid_at');
        });

        // Prescriptions — status
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index('status');
        });

        // Prescription Items — drug_id (FK without index)
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->index('drug_id');
        });

        // Dispensing Records — dispensed_at
        Schema::table('dispensing_records', function (Blueprint $table) {
            $table->index('dispensed_at');
        });

        // Lab Requests — status, urgency
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('urgency');
        });

        // Lab Request Items — status
        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->index('status');
        });

        // Lab Results — performed_at, verified_at, is_abnormal
        Schema::table('lab_results', function (Blueprint $table) {
            $table->index('performed_at');
            $table->index('verified_at');
            $table->index('is_abnormal');
        });

        // Diagnoses — type, icd_code
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->index('type');
            $table->index('icd_code');
        });

        // Investigations — status, investigation_type
        Schema::table('investigations', function (Blueprint $table) {
            $table->index('status');
            $table->index('investigation_type');
        });

        // Visit Status Logs — to_status, timestamp
        Schema::table('visit_status_logs', function (Blueprint $table) {
            $table->index('to_status');
            $table->index('timestamp');
        });

        // Queue Entries — priority, called_at
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->index('priority');
            $table->index('called_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['billing_type']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['paid_at']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropIndex(['drug_id']);
        });

        Schema::table('dispensing_records', function (Blueprint $table) {
            $table->dropIndex(['dispensed_at']);
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['urgency']);
        });

        Schema::table('lab_request_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropIndex(['performed_at']);
            $table->dropIndex(['verified_at']);
            $table->dropIndex(['is_abnormal']);
        });

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['icd_code']);
        });

        Schema::table('investigations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['investigation_type']);
        });

        Schema::table('visit_status_logs', function (Blueprint $table) {
            $table->dropIndex(['to_status']);
            $table->dropIndex(['timestamp']);
        });

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropIndex(['called_at']);
        });
    }
};

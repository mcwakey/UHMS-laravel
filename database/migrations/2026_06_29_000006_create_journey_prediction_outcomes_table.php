<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9.10 — prediction-vs-actual outcomes so the predictive system can measure its
 * own accuracy. PRIVACY: the handoff identity is stored HASHED; no patient name, no
 * visit number, no diagnosis/clinical detail. `visit_id` is kept as an internal
 * operational reference (as journey_handoff_assignments already does) solely so the
 * evaluation can re-derive the actual outcome — the table is permission-gated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_prediction_outcomes', function (Blueprint $table) {
            $table->id();
            $table->date('prediction_date')->index();
            $table->unsignedTinyInteger('prediction_hour')->nullable();
            $table->string('handoff_identity_hash', 64)->index();
            $table->unsignedBigInteger('visit_id')->nullable()->index(); // internal ref for evaluation only

            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->string('from_department_type', 50)->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->string('to_department_type', 50)->nullable()->index();
            $table->string('cause', 50)->index();

            // Predicted (captured now).
            $table->string('predicted_risk_level', 20)->index();
            $table->unsignedTinyInteger('predicted_risk_score')->default(0);
            $table->integer('predicted_minutes_to_breach')->nullable();
            $table->integer('predicted_remaining_minutes')->nullable();
            $table->string('confidence', 20)->index();

            // Actual (filled in by evaluation later).
            $table->boolean('actual_breached')->nullable();
            $table->boolean('actual_critical_breached')->nullable();
            $table->boolean('actual_resolved')->nullable();
            $table->integer('actual_minutes_to_breach')->nullable();
            $table->integer('actual_minutes_to_resolve')->nullable();
            $table->integer('actual_time_to_acknowledge')->nullable();
            $table->integer('actual_time_to_resolve')->nullable();
            $table->timestamp('evaluated_at')->nullable()->index();

            $table->timestamps();

            // One capture per handoff identity + date + hour bucket.
            $table->unique(['handoff_identity_hash', 'prediction_date', 'prediction_hour'], 'jpo_bucket_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_prediction_outcomes');
    }
};

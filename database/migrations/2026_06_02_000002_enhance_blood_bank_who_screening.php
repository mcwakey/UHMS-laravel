<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds WHO-style donor screening, structured infectious-disease screening,
 * recipient clinical details, and compatibility/transfusion safety columns on
 * top of the existing blood bank tables. Purely additive — existing donors,
 * donations, units, requests, crossmatches and issues are preserved.
 */
return new class extends Migration
{
    /**
     * Driver-aware column check. Laravel's Schema::hasColumn() queries
     * information_schema.generation_expression, which MariaDB 10.1 lacks, so we
     * use a lean information_schema query there and the native helper on sqlite.
     */
    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        return ! empty(DB::select(
            'select column_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column]
        ));
    }

    public function up(): void
    {
        // ── Donor screening (questionnaire + physical assessment + eligibility) ──
        if (! Schema::hasTable('blood_donor_screenings')) {
            Schema::create('blood_donor_screenings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('donor_id')->constrained('blood_donors')->cascadeOnDelete();
                $table->foreignId('donation_id')->nullable()->constrained('blood_donations')->nullOnDelete();
                $table->string('stage')->default('QUESTIONNAIRE_PENDING');

                // Questionnaire (structured answers stored as JSON text — MariaDB 10.1 safe)
                $table->longText('questionnaire')->nullable();
                $table->foreignId('questionnaire_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('questionnaire_at')->nullable();

                // Physical assessment
                $table->decimal('weight_kg', 5, 2)->nullable();
                $table->decimal('temperature_c', 4, 1)->nullable();
                $table->unsignedSmallInteger('pulse')->nullable();
                $table->unsignedSmallInteger('bp_systolic')->nullable();
                $table->unsignedSmallInteger('bp_diastolic')->nullable();
                $table->decimal('hemoglobin', 4, 1)->nullable();
                $table->string('general_appearance')->nullable();
                $table->string('venous_access')->nullable();
                $table->text('fitness_notes')->nullable();
                $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('assessed_at')->nullable();

                // Eligibility decision
                $table->string('eligibility_decision')->nullable(); // ELIGIBLE / TEMPORARILY_DEFERRED / PERMANENTLY_DEFERRED / NEEDS_REVIEW
                $table->longText('suggested_flags')->nullable();
                $table->string('deferral_type')->nullable(); // TEMPORARY / PERMANENT
                $table->text('deferral_reason')->nullable();
                $table->date('deferral_until')->nullable();
                $table->boolean('eligibility_overridden')->default(false);
                $table->text('override_reason')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();

                // Consent
                $table->boolean('consent_donate')->default(false);
                $table->boolean('consent_testing')->default(false);
                $table->boolean('consent_contact')->default(false);

                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['donor_id', 'stage']);
                $table->index('eligibility_decision');
            });
        }

        // ── Infectious-disease / laboratory screening tests per donation ──
        if (! Schema::hasTable('blood_donation_tests')) {
            Schema::create('blood_donation_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('donation_id')->constrained('blood_donations')->cascadeOnDelete();
                $table->string('test_code');
                $table->string('test_name');
                $table->boolean('mandatory')->default(true);
                $table->string('result')->default('NOT_DONE'); // NEGATIVE/POSITIVE/REACTIVE/NON_REACTIVE/INCONCLUSIVE/NOT_DONE
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('performed_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['donation_id', 'test_code']);
                $table->index('result');
            });
        }

        // ── Recipient clinical details for a blood request ──
        if (! Schema::hasTable('blood_recipients')) {
            Schema::create('blood_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blood_request_id')->constrained('blood_requests')->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
                $table->foreignId('emergency_case_id')->nullable()->constrained('emergency_cases')->nullOnDelete();
                $table->string('patient_blood_group')->nullable();
                $table->string('patient_rh_factor')->nullable();
                $table->string('diagnosis')->nullable();
                $table->text('clinical_indication')->nullable();
                $table->string('hemoglobin_level')->nullable();
                $table->string('pregnancy_status')->nullable();
                $table->boolean('previous_transfusion_reaction')->default(false);
                $table->text('previous_transfusion_reaction_notes')->nullable();
                $table->text('transfusion_history')->nullable();
                $table->text('special_requirements')->nullable();
                $table->string('requested_component_type')->nullable();
                $table->unsignedSmallInteger('units_required')->default(1);
                $table->string('urgency')->default('ROUTINE');
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('blood_request_id');
            });
        }

        // ── Donor lifecycle screening status ──
        Schema::table('blood_donors', function (Blueprint $table) {
            if (! $this->columnExists('blood_donors', 'screening_status')) {
                $table->string('screening_status')->default('REGISTERED')->after('status');
            }
            if (! $this->columnExists('blood_donors', 'deferral_type')) {
                $table->string('deferral_type')->nullable()->after('deferral_reason');
            }
        });

        // ── Blood unit approval audit ──
        Schema::table('blood_units', function (Blueprint $table) {
            if (! $this->columnExists('blood_units', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('screening_status')->constrained('users')->nullOnDelete();
            }
            if (! $this->columnExists('blood_units', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });

        // ── Crossmatch compatibility + verification ──
        Schema::table('blood_crossmatches', function (Blueprint $table) {
            foreach ([
                'recipient_blood_group' => fn () => $table->string('recipient_blood_group')->nullable(),
                'donor_blood_group' => fn () => $table->string('donor_blood_group')->nullable(),
                'component_type' => fn () => $table->string('component_type')->nullable(),
                'compatibility_status' => fn () => $table->string('compatibility_status')->nullable(),
                'verified_by' => fn () => $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(),
                'verified_at' => fn () => $table->dateTime('verified_at')->nullable(),
            ] as $column => $add) {
                if (! $this->columnExists('blood_crossmatches', $column)) {
                    $add();
                }
            }
        });

        // ── Blood issue: emergency release + structured transfusion record ──
        Schema::table('blood_issues', function (Blueprint $table) {
            $columns = [
                'is_emergency_release' => fn () => $table->boolean('is_emergency_release')->default(false),
                'emergency_release_type' => fn () => $table->string('emergency_release_type')->nullable(),
                'emergency_release_reason' => fn () => $table->text('emergency_release_reason')->nullable(),
                'compatibility_status' => fn () => $table->string('compatibility_status')->nullable(),
                'authorized_by' => fn () => $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete(),
                'witnessed_by' => fn () => $table->foreignId('witnessed_by')->nullable()->constrained('users')->nullOnDelete(),
                'transfusion_started_at' => fn () => $table->dateTime('transfusion_started_at')->nullable(),
                'transfusion_completed_at' => fn () => $table->dateTime('transfusion_completed_at')->nullable(),
                'pre_transfusion_vitals' => fn () => $table->longText('pre_transfusion_vitals')->nullable(),
                'post_transfusion_vitals' => fn () => $table->longText('post_transfusion_vitals')->nullable(),
                'reaction_occurred' => fn () => $table->boolean('reaction_occurred')->default(false),
                'reaction_type' => fn () => $table->string('reaction_type')->nullable(),
                'outcome' => fn () => $table->string('outcome')->nullable(),
            ];
            foreach ($columns as $column => $add) {
                if (! $this->columnExists('blood_issues', $column)) {
                    $add();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_recipients');
        Schema::dropIfExists('blood_donation_tests');
        Schema::dropIfExists('blood_donor_screenings');

        Schema::table('blood_donors', function (Blueprint $table) {
            foreach (['screening_status', 'deferral_type'] as $c) {
                if ($this->columnExists('blood_donors', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        Schema::table('blood_units', function (Blueprint $table) {
            foreach (['approved_by', 'approved_at'] as $c) {
                if ($this->columnExists('blood_units', $c)) {
                    if ($c === 'approved_by') {
                        $table->dropConstrainedForeignId('approved_by');
                    } else {
                        $table->dropColumn($c);
                    }
                }
            }
        });

        Schema::table('blood_crossmatches', function (Blueprint $table) {
            foreach (['recipient_blood_group', 'donor_blood_group', 'component_type', 'compatibility_status', 'verified_at'] as $c) {
                if ($this->columnExists('blood_crossmatches', $c)) {
                    $table->dropColumn($c);
                }
            }
            if ($this->columnExists('blood_crossmatches', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
            }
        });

        Schema::table('blood_issues', function (Blueprint $table) {
            foreach ([
                'is_emergency_release', 'emergency_release_type', 'emergency_release_reason',
                'compatibility_status', 'transfusion_started_at', 'transfusion_completed_at',
                'pre_transfusion_vitals', 'post_transfusion_vitals', 'reaction_occurred',
                'reaction_type', 'outcome',
            ] as $c) {
                if ($this->columnExists('blood_issues', $c)) {
                    $table->dropColumn($c);
                }
            }
            foreach (['authorized_by', 'witnessed_by'] as $c) {
                if ($this->columnExists('blood_issues', $c)) {
                    $table->dropConstrainedForeignId($c);
                }
            }
        });
    }
};

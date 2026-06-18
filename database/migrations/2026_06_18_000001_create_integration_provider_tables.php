<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External Integrations Phase 1 — shared provider registry.
 *
 * Holds provider configuration for BOTH the SMS and Payment gateway modules,
 * separated by `module_type`. Credentials live in a dedicated, encrypted table
 * so secrets never sit on the provider row and are never selected by accident.
 *
 * MariaDB-10.1 safe: no ->json() (longText), short named indexes, no partial
 * unique indexes — the "single active provider per module" rule is enforced at
 * the service layer inside a transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('module_type', 20);          // sms | payment
            $table->string('code', 60);                 // nalo_sms | mtn_momo | nalo_payment | fake_sms | fake_payment
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('environment', 10)->default('sandbox'); // sandbox | live
            $table->string('base_url')->nullable();
            $table->string('status', 20)->default('draft');        // draft|active|inactive|suspended|failed
            $table->boolean('is_active')->default(false);

            // Capability flags — let UI/services reason about a provider generically.
            $table->boolean('supports_send')->default(false);
            $table->boolean('supports_status_check')->default(false);
            $table->boolean('supports_callback')->default(false);
            $table->boolean('supports_collection')->default(false);
            $table->boolean('supports_disbursement')->default(false);
            $table->boolean('supports_refund')->default(false);
            $table->boolean('supports_balance_check')->default(false);

            $table->string('sender_id')->nullable();        // default SMS sender id / merchant label
            $table->string('callback_url')->nullable();     // configured public callback url
            $table->string('webhook_secret_hint')->nullable(); // non-secret hint only

            $table->dateTime('last_tested_at')->nullable();
            $table->string('last_test_status', 20)->nullable();
            $table->text('last_test_message')->nullable();

            $table->longText('metadata_snapshot')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['module_type', 'code'], 'integ_prov_module_code_unique');
            $table->index(['module_type', 'is_active'], 'integ_prov_module_active_idx');
            $table->index(['module_type', 'status'], 'integ_prov_module_status_idx');
        });

        Schema::create('integration_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_provider_id')
                ->constrained('integration_providers')
                ->cascadeOnDelete();
            $table->string('credential_key', 100);
            $table->longText('encrypted_value')->nullable(); // Laravel encrypted cast
            $table->boolean('is_sensitive')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['integration_provider_id', 'credential_key'], 'integ_cred_provider_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_provider_credentials');
        Schema::dropIfExists('integration_providers');
    }
};

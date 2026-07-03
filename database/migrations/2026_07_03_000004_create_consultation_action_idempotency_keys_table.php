<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultation_action_idempotency_keys')) {
            return;
        }

        Schema::create('consultation_action_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('consultation_route_id')->nullable()->constrained('visit_consultation_routes')->nullOnDelete();
            $table->string('action', 120);
            $table->string('payload_hash', 64);
            $table->string('response_reference_type')->nullable();
            $table->unsignedBigInteger('response_reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['key', 'user_id', 'action'], 'consult_action_idem_key_user_action_unique');
            $table->index(['visit_id', 'consultation_route_id', 'action'], 'consult_action_idem_visit_route_action_idx');
            $table->index(['response_reference_type', 'response_reference_id'], 'consult_action_idem_response_idx');
            $table->index('expires_at', 'consult_action_idem_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_action_idempotency_keys');
    }
};


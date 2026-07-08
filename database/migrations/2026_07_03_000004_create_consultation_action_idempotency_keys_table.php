<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'consultation_action_idempotency_keys';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            $this->repairExistingTable();

            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('key', 191);
            $table->foreignId('user_id')->nullable();
            $table->foreignId('visit_id');
            $table->foreignId('consultation_route_id')->nullable();
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

            $table->foreign('user_id', 'consult_action_idem_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('visit_id', 'consult_action_idem_visit_fk')
                ->references('id')
                ->on('visits')
                ->cascadeOnDelete();
            $table->foreign('consultation_route_id', 'consult_action_idem_route_fk')
                ->references('id')
                ->on('visit_consultation_routes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }

    private function repairExistingTable(): void
    {
        if (! $this->hasIndex('consult_action_idem_key_user_action_unique')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(['key', 'user_id', 'action'], 'consult_action_idem_key_user_action_unique');
            });
        }

        if (! $this->hasIndex('consult_action_idem_visit_route_action_idx')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['visit_id', 'consultation_route_id', 'action'], 'consult_action_idem_visit_route_action_idx');
            });
        }

        if (! $this->hasIndex('consult_action_idem_response_idx')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['response_reference_type', 'response_reference_id'], 'consult_action_idem_response_idx');
            });
        }

        if (! $this->hasIndex('consult_action_idem_expires_idx')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index('expires_at', 'consult_action_idem_expires_idx');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (! $this->hasForeignKey('user_id', 'users')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign('user_id', 'consult_action_idem_user_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! $this->hasForeignKey('visit_id', 'visits')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign('visit_id', 'consult_action_idem_visit_fk')
                    ->references('id')
                    ->on('visits')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->hasForeignKey('consultation_route_id', 'visit_consultation_routes')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign('consultation_route_id', 'consult_action_idem_route_fk')
                    ->references('id')
                    ->on('visit_consultation_routes')
                    ->nullOnDelete();
            });
        }
    }

    private function hasIndex(string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA index_list('.self::TABLE.')'))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        return (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [self::TABLE, $index],
        )->aggregate ?? 0) > 0;
    }

    private function hasForeignKey(string $column, string $referencedTable): bool
    {
        return (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?',
            [self::TABLE, $column, $referencedTable],
        )->aggregate ?? 0) > 0;
    }
};

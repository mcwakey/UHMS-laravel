<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds payment-reversal / refund support to the payments table:
 *   - status              active | reversed | reversal
 *   - is_reversal         marks a negative offsetting payment
 *   - reversed_payment_id links a reversal back to the original payment
 *   - reversed_at / reversed_by / reversal_reason audit fields
 *   - deleted_at          soft deletes (audit-safe; no more hard deletes)
 */
return new class extends Migration
{
    private array $columns = [
        'status'              => "VARCHAR(20) NOT NULL DEFAULT 'active'",
        'is_reversal'         => 'TINYINT(1) NOT NULL DEFAULT 0',
        'reversed_payment_id' => 'BIGINT UNSIGNED NULL DEFAULT NULL',
        'reversed_at'         => 'TIMESTAMP NULL DEFAULT NULL',
        'reversed_by'         => 'BIGINT UNSIGNED NULL DEFAULT NULL',
        'reversal_reason'     => 'VARCHAR(500) NULL DEFAULT NULL',
        'deleted_at'          => 'TIMESTAMP NULL DEFAULT NULL',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'status')) {
                    $table->string('status', 20)->default('active');
                }
                if (! Schema::hasColumn('payments', 'is_reversal')) {
                    $table->boolean('is_reversal')->default(false);
                }
                if (! Schema::hasColumn('payments', 'reversed_payment_id')) {
                    $table->unsignedBigInteger('reversed_payment_id')->nullable();
                }
                if (! Schema::hasColumn('payments', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable();
                }
                if (! Schema::hasColumn('payments', 'reversed_by')) {
                    $table->unsignedBigInteger('reversed_by')->nullable();
                }
                if (! Schema::hasColumn('payments', 'reversal_reason')) {
                    $table->string('reversal_reason', 500)->nullable();
                }
                if (! Schema::hasColumn('payments', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            return;
        }

        foreach ($this->columns as $name => $definition) {
            if (! $this->mysqlColumnExists('payments', $name)) {
                DB::statement("ALTER TABLE `payments` ADD COLUMN `{$name}` {$definition}");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payments', function (Blueprint $table) {
                foreach (array_keys($this->columns) as $name) {
                    if ($name === 'deleted_at') {
                        if (Schema::hasColumn('payments', 'deleted_at')) {
                            $table->dropSoftDeletes();
                        }
                        continue;
                    }
                    if (Schema::hasColumn('payments', $name)) {
                        $table->dropColumn($name);
                    }
                }
            });

            return;
        }

        foreach (array_keys($this->columns) as $name) {
            if ($this->mysqlColumnExists('payments', $name)) {
                DB::statement("ALTER TABLE `payments` DROP COLUMN `{$name}`");
            }
        }
    }

    private function mysqlColumnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $row && (int) $row->cnt > 0;
    }
};

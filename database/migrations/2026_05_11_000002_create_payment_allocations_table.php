<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-line payment allocation. One Payment can be split across many invoice items.
 * Uses raw SQL to bypass Laravel 12 information_schema introspection on legacy MariaDB.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_allocations')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::create('payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->timestamps();
            });

            return;
        }

        DB::statement("
            CREATE TABLE payment_allocations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                payment_id BIGINT UNSIGNED NOT NULL,
                invoice_item_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                INDEX payment_alloc_payment_idx (payment_id),
                INDEX payment_alloc_item_idx (invoice_item_id),
                CONSTRAINT fk_pa_payment      FOREIGN KEY (payment_id)      REFERENCES payments(id)      ON DELETE CASCADE,
                CONSTRAINT fk_pa_invoice_item FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS payment_allocations");
    }
};

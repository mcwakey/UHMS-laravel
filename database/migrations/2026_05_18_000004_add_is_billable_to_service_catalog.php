<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * G6 (Products/Services Analysis): symmetric `is_billable` flag on services.
 *
 * Mirrors `products.is_billable`. Defaults TRUE so existing services keep
 * their current behaviour. Set to FALSE on administrative / registration
 * services that should not generate invoice lines.
 *
 * NOTE: Uses raw information_schema lookup instead of Schema::hasColumn()
 * because the host MariaDB version pre-dates the `generation_expression`
 * column that Laravel 12's schema introspector queries.
 *
 * BillingService::addItemToVisitInvoice() guards on this flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->columnExists('service_catalog', 'is_billable')) {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->boolean('is_billable')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if ($this->columnExists('service_catalog', 'is_billable')) {
            Schema::table('service_catalog', function (Blueprint $table) {
                $table->dropColumn('is_billable');
            });
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $db = DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$db, $table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};


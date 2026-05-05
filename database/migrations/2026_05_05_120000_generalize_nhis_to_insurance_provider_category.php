<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_providers')) {
            DB::table('insurance_providers')->where('type', 'nhia')->update(['type' => 'public']);
            DB::table('insurance_providers')->where('type', 'nhis')->update(['type' => 'public']);
        }

        if (Schema::hasTable('service_prices')) {
            DB::table('service_prices')->where('insurance_type', 'nhia')->update(['insurance_type' => 'public']);
            DB::table('service_prices')->where('insurance_type', 'nhis')->update(['insurance_type' => 'public']);
        }

        if (Schema::hasTable('invoices')) {
            DB::table('invoices')->where('billing_type', 'nhis')->update(['billing_type' => 'insurance']);
        }

        if (Schema::hasTable('payments')) {
            DB::table('payments')->where('payment_method', 'nhis')->update(['payment_method' => 'insurance']);
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('group', 'payment')
                ->where('key', 'nhis_enabled')
                ->update(['key' => 'insurance_enabled']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('group', 'payment')
                ->where('key', 'insurance_enabled')
                ->update(['key' => 'nhis_enabled']);
        }

        if (Schema::hasTable('payments')) {
            DB::table('payments')->where('payment_method', 'insurance')->update(['payment_method' => 'nhis']);
        }

        if (Schema::hasTable('invoices')) {
            DB::table('invoices')->where('billing_type', 'insurance')->update(['billing_type' => 'nhis']);
        }

        if (Schema::hasTable('service_prices')) {
            DB::table('service_prices')->where('insurance_type', 'public')->update(['insurance_type' => 'nhia']);
        }

        if (Schema::hasTable('insurance_providers')) {
            DB::table('insurance_providers')->where('type', 'public')->update(['type' => 'nhia']);
        }
    }
};
 
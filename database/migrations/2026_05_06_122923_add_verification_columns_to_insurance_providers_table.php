<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function existingColumns(): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return array_map(fn ($r) => $r->name, DB::select('PRAGMA table_info(insurance_providers)'));
        }
        if (in_array($driver, ['mysql', 'mariadb'])) {
            return array_map(fn ($r) => $r->Field, DB::select('SHOW COLUMNS FROM insurance_providers'));
        }
        // Fallback for pgsql/sqlsrv etc.
        return Schema::getColumnListing('insurance_providers');
    }

    public function up(): void
    {
        $existing = $this->existingColumns();
        Schema::table('insurance_providers', function (Blueprint $table) use ($existing) {
            if (! in_array('verification_driver', $existing)) {
                $table->string('verification_driver', 40)->nullable()->after('is_default');
            }
            if (! in_array('verification_method', $existing)) {
                $table->string('verification_method', 60)->nullable()->after('verification_driver');
            }
            if (! in_array('verification_channel', $existing)) {
                $table->string('verification_channel', 60)->nullable()->after('verification_method');
            }
            if (! in_array('verification_config', $existing)) {
                $table->longText('verification_config')->nullable()->after('verification_channel');
            }
            if (! in_array('verification_credentials_key', $existing)) {
                $table->string('verification_credentials_key', 60)->nullable()->after('verification_config');
            }
        });
    }

    public function down(): void
    {
        $existing = $this->existingColumns();
        Schema::table('insurance_providers', function (Blueprint $table) use ($existing) {
            foreach ([
                'verification_driver',
                'verification_method',
                'verification_channel',
                'verification_config',
                'verification_credentials_key',
            ] as $col) {
                if (in_array($col, $existing)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

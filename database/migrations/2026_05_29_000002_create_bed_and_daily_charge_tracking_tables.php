<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createChargeTable('emergency_bed_charges', true);
        $this->createChargeTable('emergency_daily_consumable_charges', true);
        $this->createChargeTable('admission_bed_charges', false);
        $this->createChargeTable('admission_daily_consumable_charges', false);
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_daily_consumable_charges');
        Schema::dropIfExists('admission_bed_charges');
        Schema::dropIfExists('emergency_daily_consumable_charges');
        Schema::dropIfExists('emergency_bed_charges');
    }

    private function createChargeTable(string $tableName, bool $emergency): void
    {
        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($emergency, $tableName) {
            $table->id();
            if ($emergency) {
                $table->unsignedBigInteger('emergency_case_id')->index();
                $table->unsignedBigInteger('emergency_bay_assignment_id')->nullable()->index($tableName.'_assignment_idx');
                $table->unsignedBigInteger('emergency_bay_id')->nullable()->index();
            } else {
                $table->unsignedBigInteger('admission_id')->index();
            }
            $table->unsignedBigInteger('visit_id')->index();
            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('bed_id')->nullable()->index();
            $table->unsignedBigInteger('ward_id')->nullable()->index();
            $table->dateTime('started_at')->index();
            $table->dateTime('ended_at')->nullable();
            $table->string('billing_unit', 40)->default('PER_DAY');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_item_id')->nullable()->index();
            $table->string('status', 40)->default('ACTIVE')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('ended_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $table]
        );

        return (int) ($row->aggregate ?? 0) > 0;
    }
};

<?php

use App\Enums\TheatreRoomBlockType;
use App\Enums\TheatreRoomStatus;
use App\Enums\TheatreRoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('theatre_rooms')) {
            Schema::table('theatre_rooms', function (Blueprint $table) {
                if (! $this->columnExists('theatre_rooms', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (! $this->columnExists('theatre_rooms', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('code');
                }
                if (! $this->columnExists('theatre_rooms', 'room_type')) {
                    $table->string('room_type')->default(TheatreRoomType::PROCEDURE_ROOM->value)->after('department_id');
                }
                if (! $this->columnExists('theatre_rooms', 'capacity')) {
                    $table->unsignedSmallInteger('capacity')->nullable()->after('room_type');
                }
                if (! $this->columnExists('theatre_rooms', 'status')) {
                    $table->string('status')->default(TheatreRoomStatus::AVAILABLE->value)->after('location');
                }
            });

            DB::table('theatre_rooms')
                ->whereNull('code')
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($room) {
                    DB::table('theatre_rooms')
                        ->where('id', $room->id)
                        ->update([
                            'code' => 'TR-' . str_pad((string) $room->id, 4, '0', STR_PAD_LEFT),
                            'room_type' => TheatreRoomType::PROCEDURE_ROOM->value,
                            'status' => TheatreRoomStatus::AVAILABLE->value,
                        ]);
                });

            Schema::table('theatre_rooms', function (Blueprint $table) {
                $table->unique('code', 'theatre_rooms_code_unique');
                $table->index('department_id', 'theatre_rooms_department_idx');
                $table->index(['room_type', 'status'], 'theatre_rooms_type_status_idx');
            });
        }

        if (! $this->tableExists('theatre_room_blocks')) {
            Schema::create('theatre_room_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('theatre_room_id')->constrained('theatre_rooms')->cascadeOnDelete();
                $table->string('block_type')->default(TheatreRoomBlockType::OTHER->value);
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['theatre_room_id', 'start_at', 'end_at'], 'theatre_room_blocks_room_window_idx');
                $table->index('block_type', 'theatre_room_blocks_type_idx');
            });
        }

        if ($this->tableExists('procedure_schedules')) {
            Schema::table('procedure_schedules', function (Blueprint $table) {
                if (! $this->columnExists('procedure_schedules', 'expected_duration_minutes')) {
                    $table->unsignedSmallInteger('expected_duration_minutes')->nullable()->after('scheduled_end');
                }
                if (! $this->columnExists('procedure_schedules', 'override_reason')) {
                    $table->text('override_reason')->nullable()->after('notes');
                }

                $table->index(['theatre_room_id', 'scheduled_start', 'scheduled_end'], 'procedure_schedules_room_window_idx');
                $table->index(['status', 'is_current'], 'procedure_schedules_status_current_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->tableExists('procedure_schedules')) {
            Schema::table('procedure_schedules', function (Blueprint $table) {
                $table->dropIndex('procedure_schedules_room_window_idx');
                $table->dropIndex('procedure_schedules_status_current_idx');
                if ($this->columnExists('procedure_schedules', 'expected_duration_minutes')) {
                    $table->dropColumn('expected_duration_minutes');
                }
                if ($this->columnExists('procedure_schedules', 'override_reason')) {
                    $table->dropColumn('override_reason');
                }
            });
        }

        Schema::dropIfExists('theatre_room_blocks');

        if ($this->tableExists('theatre_rooms')) {
            Schema::table('theatre_rooms', function (Blueprint $table) {
                $table->dropUnique('theatre_rooms_code_unique');
                $table->dropIndex('theatre_rooms_department_idx');
                $table->dropIndex('theatre_rooms_type_status_idx');
                foreach (['code', 'department_id', 'room_type', 'capacity', 'status'] as $column) {
                    if ($this->columnExists('theatre_rooms', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function tableExists(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasTable($table);
        }

        return ! empty(DB::select(
            'select 1 from information_schema.tables where table_schema = database() and table_name = ? limit 1',
            [$table]
        ));
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        return ! empty(DB::select(
            'select 1 from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
            [$table, $column]
        ));
    }
};
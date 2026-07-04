<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_tasks', 'frequency')) {
                $table->string('frequency')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('consultation_tasks', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('frequency');
            }
            if (! Schema::hasColumn('consultation_tasks', 'is_prn')) {
                $table->boolean('is_prn')->default(false)->after('scheduled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultation_tasks', function (Blueprint $table) {
            foreach (['is_prn', 'scheduled_at', 'frequency'] as $column) {
                if (Schema::hasColumn('consultation_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

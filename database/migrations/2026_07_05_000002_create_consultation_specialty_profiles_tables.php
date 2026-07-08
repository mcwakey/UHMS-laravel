<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createProfilesTable();
        $this->createSectionsTable();
        $this->createTemplatesTable();
        $this->createEntriesTable();
        $this->createPreferencesTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_consultation_preferences');
        Schema::dropIfExists('consultation_specialty_entries');
        Schema::dropIfExists('consultation_specialty_templates');
        Schema::dropIfExists('consultation_specialty_sections');
        Schema::dropIfExists('consultation_specialty_profiles');
    }

    private function createProfilesTable(): void
    {
        if (Schema::hasTable('consultation_specialty_profiles')) {
            return;
        }

        Schema::create('consultation_specialty_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_type')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createSectionsTable(): void
    {
        if (Schema::hasTable('consultation_specialty_sections')) {
            $this->repairSectionsTable();

            return;
        }

        Schema::create('consultation_specialty_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles', indexName: 'css_sections_profile_fk')
                ->cascadeOnDelete();
            $table->string('section_key');
            $table->string('label');
            $table->string('component')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['consultation_specialty_profile_id', 'section_key'], 'consult_specialty_section_profile_key_unique');
            $table->index(['consultation_specialty_profile_id', 'display_order'], 'consult_specialty_section_profile_order_idx');
        });
    }

    private function createTemplatesTable(): void
    {
        if (Schema::hasTable('consultation_specialty_templates')) {
            $this->addForeignKeyIfMissing(
                'consultation_specialty_templates',
                'consultation_specialty_profile_id',
                'consultation_specialty_profiles',
                'css_templates_profile_fk',
                'cascade'
            );

            return;
        }

        Schema::create('consultation_specialty_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_specialty_profile_id')
                ->constrained('consultation_specialty_profiles', indexName: 'css_templates_profile_fk')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('schema')->nullable();
            $table->json('summary_mapper')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function createEntriesTable(): void
    {
        if (Schema::hasTable('consultation_specialty_entries')) {
            $this->repairEntriesTable();

            return;
        }

        Schema::create('consultation_specialty_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')
                ->nullable()
                ->constrained('visit_consultation_routes', indexName: 'css_entries_consultation_fk')
                ->nullOnDelete();
            $table->foreignId('consultation_specialty_profile_id')
                ->nullable()
                ->constrained('consultation_specialty_profiles', indexName: 'css_entries_profile_fk')
                ->nullOnDelete();
            $table->string('section_key');
            $table->json('entry')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consultation_id', 'consultation_specialty_profile_id'], 'consult_specialty_entries_consult_profile_idx');
            $table->index(['section_key'], 'consult_specialty_entries_section_key_idx');
        });
    }

    private function createPreferencesTable(): void
    {
        if (Schema::hasTable('doctor_consultation_preferences')) {
            $this->repairPreferencesTable();

            return;
        }

        Schema::create('doctor_consultation_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('default_consultation_specialty_profile_id')
                ->nullable()
                ->constrained('consultation_specialty_profiles', indexName: 'doctor_consult_prefs_profile_fk')
                ->nullOnDelete();
            $table->foreignId('default_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->json('pinned_actions')->nullable();
            $table->string('preferred_layout')->nullable();
            $table->boolean('compact_mode')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    private function repairSectionsTable(): void
    {
        if (! $this->hasIndex('consultation_specialty_sections', 'consult_specialty_section_profile_key_unique')) {
            Schema::table('consultation_specialty_sections', function (Blueprint $table) {
                $table->unique(['consultation_specialty_profile_id', 'section_key'], 'consult_specialty_section_profile_key_unique');
            });
        }

        if (! $this->hasIndex('consultation_specialty_sections', 'consult_specialty_section_profile_order_idx')) {
            Schema::table('consultation_specialty_sections', function (Blueprint $table) {
                $table->index(['consultation_specialty_profile_id', 'display_order'], 'consult_specialty_section_profile_order_idx');
            });
        }

        $this->addForeignKeyIfMissing(
            'consultation_specialty_sections',
            'consultation_specialty_profile_id',
            'consultation_specialty_profiles',
            'css_sections_profile_fk',
            'cascade'
        );
    }

    private function repairEntriesTable(): void
    {
        if (! $this->hasIndex('consultation_specialty_entries', 'consult_specialty_entries_consult_profile_idx')) {
            Schema::table('consultation_specialty_entries', function (Blueprint $table) {
                $table->index(['consultation_id', 'consultation_specialty_profile_id'], 'consult_specialty_entries_consult_profile_idx');
            });
        }

        if (! $this->hasIndex('consultation_specialty_entries', 'consult_specialty_entries_section_key_idx')) {
            Schema::table('consultation_specialty_entries', function (Blueprint $table) {
                $table->index(['section_key'], 'consult_specialty_entries_section_key_idx');
            });
        }

        $this->addForeignKeyIfMissing(
            'consultation_specialty_entries',
            'consultation_id',
            'visit_consultation_routes',
            'css_entries_consultation_fk',
            'set null'
        );
        $this->addForeignKeyIfMissing(
            'consultation_specialty_entries',
            'consultation_specialty_profile_id',
            'consultation_specialty_profiles',
            'css_entries_profile_fk',
            'set null'
        );
    }

    private function repairPreferencesTable(): void
    {
        if (! $this->hasIndex('doctor_consultation_preferences', 'doctor_consultation_preferences_user_id_unique')) {
            Schema::table('doctor_consultation_preferences', function (Blueprint $table) {
                $table->unique('user_id');
            });
        }

        $this->addForeignKeyIfMissing(
            'doctor_consultation_preferences',
            'default_consultation_specialty_profile_id',
            'consultation_specialty_profiles',
            'doctor_consult_prefs_profile_fk',
            'set null'
        );
    }

    private function addForeignKeyIfMissing(string $table, string $column, string $referencedTable, string $name, string $onDelete): void
    {
        if (DB::getDriverName() === 'sqlite' || $this->hasForeignKey($table, $column, $referencedTable)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $name, $onDelete) {
            $foreign = $blueprint->foreign($column, $name)
                ->references('id')
                ->on($referencedTable);

            if ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } elseif ($onDelete === 'set null') {
                $foreign->nullOnDelete();
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select('PRAGMA index_list('.$table.')'))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        return (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index],
        )->aggregate ?? 0) > 0;
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable): bool
    {
        return (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?',
            [$table, $column, $referencedTable],
        )->aggregate ?? 0) > 0;
    }
};

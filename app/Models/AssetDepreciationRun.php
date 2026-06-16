<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDepreciationRun extends Model
{
    protected $fillable = [
        'accounting_period_id', 'run_number', 'period_start', 'period_end',
        'status', 'total_depreciation', 'journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'total_depreciation' => 'decimal:2'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AssetDepreciationLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

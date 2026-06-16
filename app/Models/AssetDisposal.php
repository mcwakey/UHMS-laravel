<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'fixed_asset_id', 'disposal_date', 'proceeds_amount', 'carrying_amount',
        'gain_amount', 'loss_amount', 'proceeds_account_id', 'journal_entry_id',
        'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'proceeds_amount' => 'decimal:2',
            'carrying_amount' => 'decimal:2',
            'gain_amount' => 'decimal:2',
            'loss_amount' => 'decimal:2',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}

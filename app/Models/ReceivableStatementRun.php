<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivableStatementRun extends Model
{
    protected $fillable = [
        'statement_number', 'payer_type', 'payer_id', 'payer_name_snapshot',
        'period_start', 'period_end', 'status', 'opening_balance', 'charges',
        'payments', 'credit_notes', 'writeoffs', 'closing_balance',
        'generated_by', 'generated_at', 'approved_by', 'approved_at',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'opening_balance' => 'decimal:2',
            'charges' => 'decimal:2',
            'payments' => 'decimal:2',
            'credit_notes' => 'decimal:2',
            'writeoffs' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceivableStatementItem::class);
    }
}

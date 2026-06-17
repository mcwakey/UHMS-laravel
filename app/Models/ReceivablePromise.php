<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePromise extends Model
{
    protected $fillable = [
        'receivable_case_id', 'promised_by', 'promise_date', 'expected_payment_date',
        'promised_amount', 'status', 'fulfilled_amount', 'fulfilled_at', 'broken_at',
        'broken_reason', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'promise_date' => 'date',
            'expected_payment_date' => 'date',
            'promised_amount' => 'decimal:2',
            'fulfilled_amount' => 'decimal:2',
            'fulfilled_at' => 'datetime',
            'broken_at' => 'datetime',
        ];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableCaseItem extends Model
{
    protected $fillable = [
        'receivable_case_id', 'source_type', 'source_id', 'invoice_id', 'invoice_number',
        'claim_id', 'payer_type', 'payer_id', 'original_amount', 'outstanding_amount',
        'disputed_amount', 'promised_amount', 'due_date', 'aging_bucket', 'status',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'disputed_amount' => 'decimal:2',
            'promised_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }

    public function invoiceReceivable(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceivable::class, 'source_id');
    }
}

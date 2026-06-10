<?php

namespace App\Models;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'drug_id',
        'product_id',
        'stock_location_id',
        'movement_type',
        'direction',
        'quantity',
        'unit_cost',
        'total_cost',
        'valuation_method',
        'batch_no',
        'expiry_date',
        'source_type',
        'source_id',
        'performed_by',
        'movement_date',
        'notes',
        'journal_entry_id',
        'accounting_status',
        'accounting_posted_at',
        'accounting_error',
        'reversal_journal_entry_id',
    ];

    protected $casts = [
        'movement_type' => StockMovementType::class,
        'direction'     => StockMovementDirection::class,
        'quantity'      => 'decimal:4',
        'unit_cost'     => 'decimal:2',
        'total_cost'    => 'decimal:2',
        'expiry_date'   => 'date',
        'movement_date' => 'datetime',
        'accounting_posted_at' => 'datetime',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}

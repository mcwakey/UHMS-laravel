<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'entry_date',
        'entry_type',
        'source_type',
        'source_id',
        'description',
        'debit',
        'credit',
        'balance_after',
        'created_by',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'debit'         => 'decimal:4',
        'credit'        => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public const TYPE_PURCHASE_ORDER     = 'PURCHASE_ORDER';
    public const TYPE_GOODS_RECEIVED     = 'GOODS_RECEIVED';
    public const TYPE_SUPPLIER_INVOICE   = 'SUPPLIER_INVOICE';
    public const TYPE_PAYMENT            = 'PAYMENT';
    public const TYPE_RETURN_TO_SUPPLIER = 'RETURN_TO_SUPPLIER';
    public const TYPE_CREDIT_NOTE        = 'CREDIT_NOTE';
    public const TYPE_DEBIT_NOTE         = 'DEBIT_NOTE';
    public const TYPE_ADJUSTMENT         = 'ADJUSTMENT';

    public static function types(): array
    {
        return [
            self::TYPE_PURCHASE_ORDER,
            self::TYPE_GOODS_RECEIVED,
            self::TYPE_SUPPLIER_INVOICE,
            self::TYPE_PAYMENT,
            self::TYPE_RETURN_TO_SUPPLIER,
            self::TYPE_CREDIT_NOTE,
            self::TYPE_DEBIT_NOTE,
            self::TYPE_ADJUSTMENT,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDiscount extends Model
{
    use HasFactory;

    public const ACTION_APPLIED = 'applied';
    public const ACTION_REMOVED = 'removed';

    protected $fillable = [
        'invoice_id',
        'invoice_item_id',
        'action',
        'line_total',
        'old_discount_amount',
        'new_discount_amount',
        'old_patient_payable',
        'new_patient_payable',
        'old_balance',
        'new_balance',
        'is_override',
        'reason',
        'performed_by',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'line_total' => 'decimal:2',
            'old_discount_amount' => 'decimal:2',
            'new_discount_amount' => 'decimal:2',
            'old_patient_payable' => 'decimal:2',
            'new_patient_payable' => 'decimal:2',
            'old_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
            'is_override' => 'boolean',
            'performed_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

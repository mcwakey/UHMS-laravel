<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProviderCallback extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'provider_code',
        'event_type',
        'provider_transaction_id',
        'payment_reference',
        'signature_valid',
        'processed',
        'processed_at',
        'processing_error',
        'raw_payload',
        'headers_snapshot',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'processed_at' => 'datetime',
            'raw_payload' => 'array',
            'headers_snapshot' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentProviderTransaction::class, 'payment_reference', 'payment_reference');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableDunningNotice extends Model
{
    protected $fillable = [
        'receivable_case_id', 'notice_number', 'notice_level', 'notice_date',
        'delivery_channel', 'recipient_name', 'recipient_contact', 'subject',
        'body', 'status', 'generated_by', 'generated_at', 'sent_by', 'sent_at',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return ['notice_date' => 'date', 'generated_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }
}

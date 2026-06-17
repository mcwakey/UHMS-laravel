<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableFollowup extends Model
{
    protected $fillable = [
        'receivable_case_id', 'followup_type', 'followup_date', 'next_followup_date',
        'contact_person', 'contact_channel', 'summary', 'outcome', 'created_by',
    ];

    protected function casts(): array
    {
        return ['followup_date' => 'date', 'next_followup_date' => 'date'];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPostingAttemptEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'accounting_posting_attempt_id',
        'event_type',
        'from_status',
        'to_status',
        'error_code',
        'error_message',
        'context',
        'actor_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AccountingPostingAttempt::class, 'accounting_posting_attempt_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

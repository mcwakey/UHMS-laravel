<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_request_item_id',
        'lab_request_id',
        'result_type',
        'result_value',
        'result_text',
        'result_file',
        'result_file_name',
        'is_abnormal',
        'remarks',
        'performed_by',
        'verified_by',
        'performed_at',
        'verified_at',
    ];

    protected $casts = [
        'result_type' => \App\Enums\ResultType::class,
        'is_abnormal' => 'boolean',
        'performed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'lab_request_item_id');
    }

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_by);
    }

    public function getDisplayResultAttribute(): string
    {
        return match ($this->result_type) {
            \App\Enums\ResultType::RICHTEXT   => $this->result_text ?? '',
            \App\Enums\ResultType::IMAGE,
            \App\Enums\ResultType::DOCUMENT   => $this->result_file_name ?? $this->result_file ?? '',
            default                           => $this->result_value ?? '',
        };
    }
}

<?php

namespace App\Models;

use App\Enums\FrontDesk\CourierHandoffAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single custody event in a courier item's handover trail (Phase 18C).
 * Non-clinical; carries no patient content.
 */
class FrontDeskCourierHandoff extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_log_id',
        'from_user_id',
        'to_user_id',
        'from_department_id',
        'to_department_id',
        'action',
        'action_at',
        'note',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'action' => CourierHandoffAction::class,
            'action_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function courierLog(): BelongsTo
    {
        return $this->belongsTo(FrontDeskCourierLog::class, 'courier_log_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

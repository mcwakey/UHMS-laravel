<?php

namespace App\Models;

use App\Enums\NursingTaskStatus;
use App\Enums\NursingTaskType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NursingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'visit_id',
        'assigned_to',
        'created_by',
        'task_type',
        'title',
        'description',
        'priority',
        'status',
        'due_at',
        'completed_at',
        'completed_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'task_type' => NursingTaskType::class,
            'status' => NursingTaskStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [NursingTaskStatus::OPEN->value, NursingTaskStatus::IN_PROGRESS->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status?->isOpen() && $this->due_at && $this->due_at->isPast();
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'nursing_task',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}

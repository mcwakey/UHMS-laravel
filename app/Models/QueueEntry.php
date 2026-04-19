<?php

namespace App\Models;

use App\Enums\Priority;
use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'visit_id',
        'department_id',
        'queue_number',
        'priority',
        'status',
        'called_at',
        'served_at',
        'completed_at',
        'served_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function nextQueueNumber(?int $departmentId): int
    {
        $last = static::where('department_id', $departmentId)
            ->whereDate('created_at', today())
            ->max('queue_number');

        return ($last ?? 0) + 1;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'waiting' => 'warning',
            'serving' => 'primary',
            'completed' => 'success',
            'skipped' => 'secondary',
            default => 'light',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}

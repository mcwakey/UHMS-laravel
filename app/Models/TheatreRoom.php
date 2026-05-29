<?php

namespace App\Models;

use App\Enums\TheatreRoomStatus;
use App\Enums\TheatreRoomType;
use App\Models\TheatreRoomBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TheatreRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'room_type',
        'capacity',
        'location',
        'status',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'room_type' => TheatreRoomType::class,
        'status' => TheatreRoomStatus::class,
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSchedulable(Builder $q): Builder
    {
        return $q->active()->whereIn('status', array_map(
            fn (TheatreRoomStatus $status) => $status->value,
            array_filter(TheatreRoomStatus::cases(), fn (TheatreRoomStatus $status) => $status->isSchedulable())
        ));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProcedureSchedule::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TheatreRoomBlock::class);
    }

    public function isSchedulable(): bool
    {
        return $this->is_active && ($this->status?->isSchedulable() ?? false);
    }
}

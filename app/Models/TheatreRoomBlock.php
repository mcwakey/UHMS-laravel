<?php

namespace App\Models;

use App\Enums\TheatreRoomBlockType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheatreRoomBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'theatre_room_id',
        'block_type',
        'start_at',
        'end_at',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'block_type' => TheatreRoomBlockType::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function theatreRoom(): BelongsTo
    {
        return $this->belongsTo(TheatreRoom::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
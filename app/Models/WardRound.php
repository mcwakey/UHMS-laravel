<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WardRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'recorded_by',
        'round_date',
        'notes',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'round_date' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

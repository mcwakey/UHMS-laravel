<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencySessionContributor extends Model
{
    protected $fillable = [
        'emergency_session_id',
        'emergency_case_id',
        'user_id',
        'role',
        'first_contributed_at',
        'last_contributed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_contributed_at' => 'datetime',
            'last_contributed_at' => 'datetime',
        ];
    }

    public function emergencySession()
    {
        return $this->belongsTo(\App\Models\EmergencySession::class);
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
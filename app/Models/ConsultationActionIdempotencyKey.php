<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationActionIdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'user_id',
        'visit_id',
        'consultation_route_id',
        'action',
        'payload_hash',
        'response_reference_type',
        'response_reference_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}


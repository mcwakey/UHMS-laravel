<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSessionContributor extends Model
{
    protected $fillable = [
        'consultation_route_id',
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

    public function consultationRoute(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

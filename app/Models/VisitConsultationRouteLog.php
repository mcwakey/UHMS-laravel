<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitConsultationRouteLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_consultation_route_id',
        'visit_id',
        'from_status',
        'to_status',
        'action',
        'notes',
        'performed_by',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'visit_consultation_route_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

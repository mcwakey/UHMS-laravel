<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitConsultationRouteService extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_consultation_route_id',
        'visit_id',
        'service_id',
        'invoice_item_id',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'visit_consultation_route_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }
}

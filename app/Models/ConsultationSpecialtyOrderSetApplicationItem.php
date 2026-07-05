<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSpecialtyOrderSetApplicationItem extends Model
{
    protected $fillable = [
        'consultation_specialty_order_set_application_id',
        'consultation_specialty_order_set_item_id',
        'item_type',
        'label',
        'apply_mode',
        'status',
        'target_type',
        'target_id',
        'payload',
        'message',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'warnings' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyOrderSetApplication::class, 'consultation_specialty_order_set_application_id');
    }

    public function orderSetItem(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyOrderSetItem::class, 'consultation_specialty_order_set_item_id');
    }
}

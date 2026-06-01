<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRenderingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_rendering_id',
        'visit_id',
        'patient_id',
        'service_id',
        'from_status',
        'to_status',
        'action',
        'notes',
        'reason',
        'performed_by',
    ];

    public function serviceRendering()
    {
        return $this->belongsTo(ServiceRendering::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

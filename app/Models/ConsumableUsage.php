<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    protected $fillable = [
        'visit_id',
        'emergency_case_id',
        'emergency_session_id',
        'medical_record_id',
        'consultation_route_id',
        'patient_id',
        'service_id',
        'source_type',
        'source_id',
        'product_id',
        'stock_location_id',
        'quantity_used',
        'is_billable',
        'invoice_item_id',
        'stock_movement_id',
        'used_by',
        'used_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:4',
            'is_billable'   => 'boolean',
            'used_at'       => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession()
    {
        return $this->belongsTo(EmergencySession::class);
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function movement()
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * Patient/visit/clinical context for the activity log, so consumable usage
     * surfaces on the patient timeline with full traceability. Maps source_type
     * → the specific clinical id where known.
     */
    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'emergency_case_id' => $this->emergency_case_id,
            'medical_record_id' => $this->medical_record_id,
            'product_id' => $this->product_id,
            'stock_location_id' => $this->stock_location_id,
            'quantity' => (float) $this->quantity_used,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'invoice_item_id' => $this->invoice_item_id,
            'investigation_request_id' => $this->source_type === 'investigation_result' ? $this->source_id : null,
            'procedure_request_id' => $this->source_type === 'procedure_request' ? $this->source_id : null,
        ], fn ($v) => $v !== null);
    }
}

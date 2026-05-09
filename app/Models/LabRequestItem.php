<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabRequestItem extends Model
{
    protected $fillable = [
        'lab_request_id',
        'lab_test_id',
        'service_id',
        'status',
        'name',
    ];

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    /**
     * Resolve the display name for the request item — preferring service, then lab test, then free-text name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->name;
        }
        if ($this->service_id && $service = $this->service()->first()) {
            return $service->name;
        }
        if ($this->relationLoaded('labTest') && $this->labTest) {
            return $this->labTest->name;
        }
        if ($this->lab_test_id && $test = $this->labTest()->first()) {
            return $test->name;
        }
        return (string) ($this->name ?? '—');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            default => 'secondary',
        };
    }
}

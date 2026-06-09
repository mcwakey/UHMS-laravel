<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SponsorAuthorization extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'sponsor_id',
        'patient_id',
        'visit_id',
        'invoice_id',
        'authorization_number',
        'authorized_amount',
        'used_amount',
        'valid_from',
        'valid_until',
        'status',
        'notes',
        'authorized_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'authorized_amount' => 'decimal:2',
            'used_amount' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'authorized_amount', 'used_amount', 'valid_until'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivables()
    {
        return $this->hasMany(InvoiceReceivable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today());
            });
    }
}

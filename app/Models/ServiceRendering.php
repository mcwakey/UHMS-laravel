<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRendering extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_RENDERED = 'RENDERED';

    public const STATUS_NOT_RENDERED = 'NOT_RENDERED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_ON_HOLD = 'ON_HOLD';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
    ];

    protected $fillable = [
        'visit_id',
        'patient_id',
        'invoice_item_id',
        'service_id',
        'department_id',
        'emergency_case_id',
        'admission_id',
        'consultation_route_id',
        'rendered_by',
        'started_by',
        'started_at',
        'rendered_at',
        'status',
        'notes',
        'result_summary',
        'reason_not_rendered',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'rendered_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_RENDERED,
            self::STATUS_NOT_RENDERED,
            self::STATUS_CANCELLED,
            self::STATUS_ON_HOLD,
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class);
    }

    public function renderedBy()
    {
        return $this->belongsTo(User::class, 'rendered_by');
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs()
    {
        return $this->hasMany(ServiceRenderingLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->can('service_rendering.view_all')) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->whereNull('department_id');

            if ($user->department_id) {
                $builder->orWhere('department_id', $user->department_id);
            }
        });
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_RENDERED => 'success',
            self::STATUS_NOT_RENDERED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_ON_HOLD => 'dark',
            default => 'secondary',
        };
    }

    public function getCanBeStartedAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getCanBeClosedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ON_HOLD,
        ], true);
    }
}

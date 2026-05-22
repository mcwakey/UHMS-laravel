<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A consultation routing record for a visit.
 *
 * Statuses: PENDING (selected at visit creation), ACTIVE (currently being seen),
 * COMPLETED (consultation finished), CANCELLED.
 *
 * @property int    $id
 * @property int    $visit_id
 * @property int    $patient_id
 * @property int    $department_id
 * @property int    $service_id     references service_catalog.id
 * @property ?int   $doctor_id
 * @property string $status
 * @property ?int   $routed_by
 * @property ?int   $started_by
 * @property ?int   $completed_by
 * @property ?\Illuminate\Support\Carbon $started_at
 * @property ?\Illuminate\Support\Carbon $completed_at
 * @property ?string $notes
 */
class VisitConsultationRoute extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'department_id',
        'service_id',
        'doctor_id',
        'status',
        'routed_by',
        'started_by',
        'completed_by',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

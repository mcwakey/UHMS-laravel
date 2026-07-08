<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A consultation routing record for a visit.
 *
 * Statuses: PENDING (selected at visit creation), ACTIVE (currently being seen),
 * PAUSED (left open while another session is active), COMPLETED, CANCELLED.
 *
 * @property int $id
 * @property int $visit_id
 * @property int $patient_id
 * @property int $department_id
 * @property ?int $service_id legacy primary/first service reference
 * @property ?int $doctor_id
 * @property string $status
 * @property ?int $routed_by
 * @property ?int $started_by
 * @property ?int $completed_by
 * @property ?Carbon $started_at
 * @property ?Carbon $completed_at
 * @property ?string $notes
 */
class VisitConsultationRoute extends Model
{
    use HasFactory;

    public const SESSION_TYPE_CONSULTATION = 'CONSULTATION';

    public const SESSION_TYPE_EMERGENCY = 'EMERGENCY';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_PAUSED = 'PAUSED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'emergency_case_id',
        'department_id',
        'service_id',
        'doctor_id',
        'main_doctor_id',
        'primary_nurse_id',
        'session_type',
        'status',
        'routed_by',
        'started_by',
        'completed_by',
        'started_at',
        'activated_at',
        'paused_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'locked_at',
        'locked_by',
        'lock_reason',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'reopen_count',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'activated_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'locked_at' => 'datetime',
        'reopened_at' => 'datetime',
        'reopen_count' => 'integer',
    ];

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

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

    public function routeServices(): HasMany
    {
        return $this->hasMany(VisitConsultationRouteService::class, 'visit_consultation_route_id');
    }

    public function services()
    {
        return $this->belongsToMany(ServiceCatalog::class, 'visit_consultation_route_services', 'visit_consultation_route_id', 'service_id')
            ->withPivot('invoice_item_id')
            ->withTimestamps();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function mainDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_doctor_id');
    }

    public function primaryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_nurse_id');
    }

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession(): HasOne
    {
        return $this->hasOne(EmergencySession::class, 'consultation_route_id');
    }

    public function isEmergencySession(): bool
    {
        return $this->session_type === self::SESSION_TYPE_EMERGENCY || $this->emergency_case_id !== null;
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

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class, 'consultation_route_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'consultation_route_id');
    }

    public function followUpAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'consultation_route_id')
            ->orderBy('appointment_date')
            ->orderBy('start_time');
    }

    public function activeFollowUpAppointments(): HasMany
    {
        return $this->followUpAppointments()->whereNotIn('status', [
            AppointmentStatus::CANCELLED->value,
            AppointmentStatus::NO_SHOW->value,
        ]);
    }

    public function contributors(): HasMany
    {
        return $this->hasMany(ConsultationSessionContributor::class, 'consultation_route_id');
    }

    public function specialtyEntries(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyEntry::class, 'consultation_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VisitConsultationRouteLog::class, 'visit_consultation_route_id')
            ->latest();
    }
}

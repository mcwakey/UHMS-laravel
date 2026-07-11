<?php

namespace App\Models;

use App\Enums\FrontDesk\VisitorContext;
use App\Enums\FrontDesk\VisitorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front Desk visitor log (Phase 18A). Non-clinical record of a person entering
 * the facility. NOT a clinical patient "visit" — patient links are optional and
 * only safe identifiers are surfaced.
 */
class FrontDeskVisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_context',
        'visitor_name',
        'visitor_phone',
        'id_type',
        'id_number',
        'organization',
        'patient_id',
        'visit_id',
        'admission_id',
        'ward_id',
        'bed_id',
        'department_id',
        'relationship_to_patient',
        'person_to_see',
        'purpose',
        'badge_number',
        'vehicle_number',
        'time_in',
        'time_out',
        'status',
        'approved_by',
        'checked_in_by',
        'checked_out_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'visitor_context' => VisitorContext::class,
            'status' => VisitorStatus::class,
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('visitor_name', 'like', "%{$term}%")
                ->orWhere('visitor_phone', 'like', "%{$term}%")
                ->orWhere('organization', 'like', "%{$term}%")
                ->orWhere('person_to_see', 'like', "%{$term}%")
                ->orWhere('badge_number', 'like', "%{$term}%")
                ->orWhere('vehicle_number', 'like', "%{$term}%")
                ->orWhereHas('patient', function (Builder $pq) use ($term) {
                    $pq->where('patient_number', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
        });
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('time_in', '>=', $from);
        }
        if ($to) {
            $query->whereDate('time_in', '<=', $to);
        }

        return $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeVisitorContext(Builder $query, ?string $context): Builder
    {
        return $context ? $query->where('visitor_context', $context) : $query;
    }

    public function scopeDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    /** Visitors who checked in and have not checked out — "currently inside". */
    public function scopeCurrentlyInside(Builder $query): Builder
    {
        return $query->where('status', VisitorStatus::CHECKED_IN->value)
            ->whereNull('time_out');
    }

    /** Currently inside AND checked in longer ago than the configured threshold. */
    public function scopeOverdue(Builder $query, ?int $hours = null): Builder
    {
        return $query->currentlyInside()
            ->where('time_in', '<', now()->subHours($hours ?? self::overdueHours()));
    }

    /** Visitors linked to a patient folder. */
    public function scopePatientLinked(Builder $query): Builder
    {
        return $query->whereNotNull('patient_id');
    }

    /** Facility / department (non-patient) visitors. */
    public function scopeFacilityVisitor(Builder $query): Builder
    {
        return $query->whereNull('patient_id');
    }

    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForAdmission(Builder $query, int $admissionId): Builder
    {
        return $query->where('admission_id', $admissionId);
    }

    public function scopeCheckedOutToday(Builder $query): Builder
    {
        return $query->where('status', VisitorStatus::CHECKED_OUT->value)
            ->whereDate('time_out', today());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isInside(): bool
    {
        return $this->status === VisitorStatus::CHECKED_IN && $this->time_out === null;
    }

    public function isCheckedOut(): bool
    {
        return $this->status === VisitorStatus::CHECKED_OUT;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            VisitorStatus::CHECKED_OUT,
            VisitorStatus::DENIED,
            VisitorStatus::CANCELLED,
        ], true);
    }

    /** Configured overdue threshold in hours (Phase 18B key, Phase 18A fallback). */
    public static function overdueHours(): int
    {
        return (int) (config('front_desk.visitors.overdue_hours')
            ?? config('front_desk.visitor_overdue_hours', 4));
    }

    /** A currently-inside visitor whose stay has exceeded the overdue threshold. */
    public function isOverdue(): bool
    {
        return $this->isInside()
            && $this->time_in !== null
            && $this->time_in->lt(now()->subHours(self::overdueHours()));
    }

    /**
     * Minutes on site: time_in → time_out (or now while still inside).
     */
    public function durationMinutes(): ?int
    {
        if ($this->time_in === null) {
            return null;
        }

        $end = $this->time_out ?? now();

        return (int) $this->time_in->diffInMinutes($end);
    }

    /** Expected checkout = time_in + configured default visit duration. */
    public function expectedCheckoutAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->time_in === null) {
            return null;
        }

        $minutes = (int) config('front_desk.visitors.default_visit_duration_minutes', 120);

        return $this->time_in->copy()->addMinutes($minutes);
    }

    /** Optional checkout note captured at check-out (stored in metadata). */
    public function checkoutNote(): ?string
    {
        return $this->metadata['checkout_note'] ?? null;
    }
}

<?php

namespace App\Models;

use App\Enums\FrontDesk\FrontDeskIncidentSeverity;
use App\Enums\FrontDesk\FrontDeskIncidentStatus;
use App\Enums\FrontDesk\FrontDeskIncidentType;
use App\Models\Concerns\MasksFrontDeskPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front Desk / Security Desk incident log (Phase 18E). Operational, NON-clinical
 * incident tracking. Never carries diagnosis, treatment or medical details.
 */
class FrontDeskIncidentLog extends Model
{
    use HasFactory, MasksFrontDeskPhone;

    protected $fillable = [
        'incident_number', 'incident_type', 'severity', 'status',
        'reported_at', 'reported_by_name', 'reported_by_phone', 'location', 'department_id',
        'related_visitor_log_id', 'related_call_log_id', 'related_courier_log_id',
        'assigned_to_user_id', 'escalated_to_user_id', 'escalated_at',
        'resolved_by', 'resolved_at',
        'description', 'action_taken', 'resolution_note',
        'created_by', 'updated_by', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'incident_type' => FrontDeskIncidentType::class,
            'severity' => FrontDeskIncidentSeverity::class,
            'status' => FrontDeskIncidentStatus::class,
            'reported_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /* ── Relationships ───────────────────────────────────────────── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function relatedVisitorLog(): BelongsTo
    {
        return $this->belongsTo(FrontDeskVisitorLog::class, 'related_visitor_log_id');
    }

    public function relatedCallLog(): BelongsTo
    {
        return $this->belongsTo(FrontDeskCallLog::class, 'related_call_log_id');
    }

    public function relatedCourierLog(): BelongsTo
    {
        return $this->belongsTo(FrontDeskCourierLog::class, 'related_courier_log_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function escalatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ── Scopes ──────────────────────────────────────────────────── */

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('reported_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('reported_at', '<=', $to);
        }

        return $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('incident_type', $type) : $query;
    }

    public function scopeSeverity(Builder $query, ?string $severity): Builder
    {
        return $severity ? $query->where('severity', $severity) : $query;
    }

    public function scopeDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('incident_number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('reported_by_name', 'like', "%{$term}%");
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', FrontDeskIncidentStatus::openValues());
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', FrontDeskIncidentSeverity::CRITICAL->value)
            ->whereIn('status', FrontDeskIncidentStatus::openValues());
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function isOpen(): bool
    {
        return in_array($this->status->value, FrontDeskIncidentStatus::openValues(), true);
    }

    public function isCritical(): bool
    {
        return $this->severity === FrontDeskIncidentSeverity::CRITICAL;
    }

    public function isResolved(): bool
    {
        return $this->status === FrontDeskIncidentStatus::RESOLVED;
    }

    public function ageInHours(): ?int
    {
        return $this->reported_at ? (int) $this->reported_at->diffInHours(now()) : null;
    }

    public function maskedReporterPhone(): ?string
    {
        return $this->maskPhone($this->reported_by_phone);
    }

    public static function generateIncidentNumber(): string
    {
        $prefix = (string) config('front_desk.incidents.reference_prefix', 'INC');
        $date = now()->format('Ymd');
        $last = static::where('incident_number', 'like', "{$prefix}-{$date}-%")->orderByDesc('incident_number')->value('incident_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
}

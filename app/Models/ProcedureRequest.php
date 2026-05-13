<?php

namespace App\Models;

use App\Enums\ProcedureStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcedureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'visit_id', 'patient_id', 'requested_by', 'department_id',
        'service_catalog_id', 'procedure_id',
        'priority', 'indication', 'notes', 'preferred_datetime',
        'status',
        'billing_item_id', 'billed_at', 'billed_by',
        'accepted_by', 'accepted_at', 'acceptance_notes',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
        'completed_by', 'completed_at',
        'requested_at',
    ];

    protected $casts = [
        'status'             => ProcedureStatus::class,
        'preferred_datetime' => 'datetime',
        'requested_at'       => 'datetime',
        'accepted_at'        => 'datetime',
        'rejected_at'        => 'datetime',
        'cancelled_at'       => 'datetime',
        'completed_at'       => 'datetime',
        'billed_at'          => 'datetime',
    ];

    /* ── Relationships ─────────────────────────────────────────── */

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_catalog_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function billingItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'billing_item_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProcedureSchedule::class);
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(ProcedureSchedule::class)->where('is_current', true)->latestOfMany();
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(ProcedureVital::class);
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(ProcedureChecklist::class);
    }

    public function anaesthesiaNote(): HasOne
    {
        return $this->hasOne(AnaesthesiaNote::class);
    }

    public function operativeNote(): HasOne
    {
        return $this->hasOne(OperativeNote::class);
    }

    public function postOpNote(): HasOne
    {
        return $this->hasOne(PostOpNote::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ProcedureStatusLog::class)->orderBy('id');
    }

    /* ── Scopes ────────────────────────────────────────────────── */

    public function scopeStatus(Builder $q, $status)
    {
        return $q->where('status', $status instanceof ProcedureStatus ? $status->value : $status);
    }

    public function scopeOpen(Builder $q)
    {
        return $q->whereIn('status', array_map(fn ($s) => $s->value, ProcedureStatus::openStatuses()));
    }

    /* ── Helpers ───────────────────────────────────────────────── */

    public function isBilled(): bool
    {
        return $this->billing_item_id !== null;
    }

    public static function generateNumber(): string
    {
        $prefix = 'PR-' . date('Ymd') . '-';
        $last = static::where('request_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('request_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

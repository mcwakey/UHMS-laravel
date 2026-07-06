<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationSpecialtyServiceMapping extends Model
{
    public const CONTEXT_CONSULTATION = 'consultation';
    public const CONTEXT_SPECIALIST_ASSESSMENT = 'specialist_assessment';
    public const CONTEXT_INVESTIGATION = 'investigation';
    public const CONTEXT_PROCEDURE = 'procedure';
    public const CONTEXT_THERAPY_SESSION = 'therapy_session';
    public const CONTEXT_DENTAL_PROCEDURE = 'dental_procedure';
    public const CONTEXT_EYE_PROCEDURE = 'eye_procedure';
    public const CONTEXT_FOLLOW_UP = 'follow_up';
    public const CONTEXT_CONSENT_RELATED = 'consent_related';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_ON_VISIT_CREATE = 'on_visit_create';
    public const TRIGGER_ON_ROUTE_OPEN = 'on_route_open';
    public const TRIGGER_ON_CONSULTATION_START = 'on_consultation_start';
    public const TRIGGER_ON_ORDER_SET_APPLY = 'on_order_set_apply';
    public const TRIGGER_ON_SPECIALTY_ENTRY_SAVE = 'on_specialty_entry_save';
    public const TRIGGER_ON_COMPLETION = 'on_completion';

    public const CONTEXTS = [
        self::CONTEXT_CONSULTATION,
        self::CONTEXT_SPECIALIST_ASSESSMENT,
        self::CONTEXT_INVESTIGATION,
        self::CONTEXT_PROCEDURE,
        self::CONTEXT_THERAPY_SESSION,
        self::CONTEXT_DENTAL_PROCEDURE,
        self::CONTEXT_EYE_PROCEDURE,
        self::CONTEXT_FOLLOW_UP,
        self::CONTEXT_CONSENT_RELATED,
    ];

    public const TRIGGERS = [
        self::TRIGGER_MANUAL,
        self::TRIGGER_ON_VISIT_CREATE,
        self::TRIGGER_ON_ROUTE_OPEN,
        self::TRIGGER_ON_CONSULTATION_START,
        self::TRIGGER_ON_ORDER_SET_APPLY,
        self::TRIGGER_ON_SPECIALTY_ENTRY_SAVE,
        self::TRIGGER_ON_COMPLETION,
    ];

    protected $fillable = [
        'consultation_specialty_profile_id',
        'service_id',
        'department_id',
        'consultation_route_id',
        'department_type',
        'section_key',
        'mapping_context',
        'billing_trigger',
        'priority',
        'is_default',
        'auto_bill',
        'requires_confirmation',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_default' => 'boolean',
            'auto_bill' => 'boolean',
            'requires_confirmation' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyBillingApplication::class, 'consultation_specialty_service_mapping_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderByDesc('is_default')->orderBy('id');
    }

    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForProfile(Builder $query, ConsultationSpecialtyProfile $profile): Builder
    {
        return $query->where('consultation_specialty_profile_id', $profile->id);
    }

    public function scopeForContext(Builder $query, string $context): Builder
    {
        return $query->where('mapping_context', $context);
    }

    public function scopeForDepartment(Builder $query, mixed $department): Builder
    {
        $id = is_object($department) ? ($department->id ?? null) : $department;

        return $query->where('department_id', $id);
    }

    public function scopeForDepartmentType(Builder $query, ?string $departmentType): Builder
    {
        return $query->where('department_type', $departmentType);
    }
}

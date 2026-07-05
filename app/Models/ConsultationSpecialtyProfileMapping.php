<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSpecialtyProfileMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_specialty_profile_id',
        'department_id',
        'consultation_route_id',
        'department_type',
        'user_id',
        'source',
        'priority',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function consultationRoute(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('id');
    }

    public function scopeForDepartment(Builder $query, mixed $department): Builder
    {
        $departmentId = is_object($department) ? ($department->id ?? null) : $department;

        return $query->where('department_id', $departmentId);
    }

    public function scopeForConsultationRoute(Builder $query, mixed $route): Builder
    {
        $routeId = is_object($route) ? ($route->id ?? null) : $route;

        return $query->where('consultation_route_id', $routeId);
    }

    public function scopeForDepartmentType(Builder $query, ?string $departmentType): Builder
    {
        return $query->where('department_type', $departmentType);
    }
}

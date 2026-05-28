<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAlias extends Model
{
    use HasFactory;

    public const TYPE_PATIENT_NUMBER = 'patient_number';
    public const TYPE_TEMPORARY_PATIENT_NUMBER = 'temporary_patient_number';
    public const TYPE_GHANA_CARD = 'ghana_card_number';
    public const TYPE_PHONE = 'phone';

    protected $fillable = [
        'patient_id',
        'source_patient_id',
        'alias_type',
        'alias_value',
        'normalized_alias_value',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function sourcePatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'source_patient_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }
}

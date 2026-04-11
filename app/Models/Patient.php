<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers;

    protected $fillable = [
        'patient_number',
        'first_name',
        'last_name',
        'other_names',
        'date_of_birth',
        'gender',
        'blood_group',
        'marital_status',
        'phone',
        'phone_secondary',
        'email',
        'ghana_card_number',
        'nhis_number',
        'nhis_expiry_date',
        'occupation',
        'address',
        'city',
        'region',
        'digital_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'avatar',
        'allergies',
        'chronic_conditions',
        'status',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'nhis_expiry_date' => 'date',
            'gender' => Gender::class,
            'blood_group' => BloodGroup::class,
            'marital_status' => MaritalStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->other_names, $this->last_name]);
        return implode(' ', $parts);
    }

    public function getAgeAttribute(): string
    {
        return $this->date_of_birth->age;
    }

    public function getIsNhisActiveAttribute(): bool
    {
        return $this->nhis_number && $this->nhis_expiry_date && $this->nhis_expiry_date->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('other_names', 'like', "%{$term}%")
              ->orWhere('patient_number', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('ghana_card_number', 'like', "%{$term}%")
              ->orWhere('nhis_number', 'like', "%{$term}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generatePatientNumber(): string
    {
        return static::generateNumber('PT', 'patients', 'patient_number');
    }
}

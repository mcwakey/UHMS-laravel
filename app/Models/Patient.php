<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'phone', 'email', 'status'])
            ->logOnlyDirty()
            ->useLogName('patients')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'patient_number',
        'first_name',
        'last_name',
        'other_names',
        'date_of_birth',
        'height',
        'gender',
        'blood_group',
        'marital_status',
        'religion',
        'phone',
        'phone_secondary',
        'email',
        'ghana_card_number',
        'occupation',
        'address',
        'city',
        'town',
        'region',
        'digital_address',
        'avatar',
        'allergies',
        'chronic_conditions',
        'status',
        'is_temporary',
        'temporary_reason',
        'identity_confirmed_at',
        'identity_confirmed_by',
        'merged_to_patient_id',
        'registered_by',
        // Deceased fields
        'is_deceased',
        'deceased_at',
        'cause_of_death',
        'deceased_notes',
        'marked_deceased_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender'        => Gender::class,
            'blood_group'   => BloodGroup::class,
            'marital_status'=> MaritalStatus::class,
            'is_deceased'   => 'boolean',
            'deceased_at'   => 'date',
            'is_temporary'   => 'boolean',
            'identity_confirmed_at' => 'datetime',
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

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function emergencyCases()
    {
        return $this->hasMany(EmergencyCase::class);
    }

    public function activeVisit()
    {
        return $this->hasOne(Visit::class)->whereNotIn('status', ['completed', 'cancelled'])->latestOfMany();
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function insurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function activeInsurances()
    {
        return $this->hasMany(PatientInsurance::class)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    public function primaryInsurance()
    {
        return $this->hasOne(PatientInsurance::class)->where('is_primary', true);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function primaryEmergencyContact()
    {
        return $this->hasOne(EmergencyContact::class)->where('is_primary', true);
    }

    public function appointments()
    {
        return $this->hasMany(Visit::class)->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function latestVisit()
    {
        return $this->hasOne(Visit::class)->latestOfMany('visit_date');
    }

    public function markedDeceasedBy()
    {
        return $this->belongsTo(User::class, 'marked_deceased_by');
    }

    public function identityConfirmedBy()
    {
        return $this->belongsTo(User::class, 'identity_confirmed_by');
    }

    public function mergedToPatient()
    {
        return $this->belongsTo(self::class, 'merged_to_patient_id');
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

    public function getCashAndCarryInsuranceAttribute(): ?PatientInsurance
    {
        return $this->insurances()
            ->whereHas('insuranceProvider', fn ($q) => $q->where('is_default', true))
            ->first();
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
              ->orWhereHas('insurances', function ($ins) use ($term) {
                  $ins->where('membership_number', 'like', "%{$term}%");
              })
              ->orWhereHas('emergencyContacts', function ($ec) use ($term) {
                  $ec->where('name', 'like', "%{$term}%")
                     ->orWhere('phone', 'like', "%{$term}%");
              });
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

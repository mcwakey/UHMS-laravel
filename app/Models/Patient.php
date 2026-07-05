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
            ->logOnly(['first_name', 'last_name', 'status'])
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
        'is_active',
        'is_temporary',
        'temporary_reason',
        'identity_confirmed_at',
        'identity_confirmed_by',
        'merged_to_patient_id',
        'merge_status',
        'merged_at',
        'merged_by',
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
            'is_active'      => 'boolean',
            'is_temporary'   => 'boolean',
            'identity_confirmed_at' => 'datetime',
            'merged_at' => 'datetime',
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

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function pregnancyProfiles()
    {
        return $this->hasMany(PregnancyProfile::class)->latest('created_at');
    }

    public function activePregnancyProfile()
    {
        return $this->hasOne(PregnancyProfile::class)
            ->whereIn('profile_status', [
                \App\Enums\PregnancyProfileStatus::ACTIVE->value,
                \App\Enums\PregnancyProfileStatus::HIGH_RISK->value,
            ])
            ->latestOfMany();
    }

    public function maternityCases()
    {
        return $this->hasMany(MaternityCase::class)->latest('opened_at')->latest('id');
    }

    public function antenatalVisits()
    {
        return $this->hasMany(AntenatalVisit::class)->latest('visit_date')->latest('id');
    }

    public function laborEpisodes()
    {
        return $this->hasMany(LaborEpisode::class)->latest('started_at')->latest('id');
    }

    public function deliveryRecords()
    {
        return $this->hasMany(DeliveryRecord::class)->latest('delivery_at')->latest('id');
    }

    public function admissionRequests()
    {
        return $this->hasMany(AdmissionRequest::class);
    }

    public function bedReservations()
    {
        return $this->hasMany(BedReservation::class);
    }

    public function activeAdmission()
    {
        return $this->hasOne(Admission::class)
            ->whereNotIn('status', ['discharged', 'transferred', 'deceased'])
            ->latestOfMany('admission_date');
    }

    public function emergencyCases()
    {
        return $this->hasMany(EmergencyCase::class);
    }

    public function bloodDonor()
    {
        return $this->hasOne(BloodDonor::class);
    }

    public function bloodRequests()
    {
        return $this->hasMany(BloodRequest::class);
    }

    public function bloodIssues()
    {
        return $this->hasMany(BloodIssue::class);
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

    public function serviceRenderings()
    {
        return $this->hasMany(ServiceRendering::class);
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
        return $this->hasMany(Appointment::class);
    }

    public function archivedRecord()
    {
        return $this->hasOne(ArchivedPatient::class);
    }

    public function privacyOverrides()
    {
        return $this->hasMany(PatientPrivacyOverride::class);
    }

    public function privacyDirectives()
    {
        return $this->hasMany(PatientPrivacyDirective::class);
    }

    public function activePrivacyDirectives()
    {
        return $this->hasMany(PatientPrivacyDirective::class)->active();
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

    public function mergedInto()
    {
        return $this->mergedToPatient();
    }

    public function mergedFromPatients()
    {
        return $this->hasMany(self::class, 'merged_to_patient_id');
    }

    public function mergedBy()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    public function aliases()
    {
        return $this->hasMany(PatientAlias::class);
    }

    public function mergeRequestsAsMain()
    {
        return $this->hasMany(PatientMergeRequest::class, 'main_patient_id');
    }

    public function mergeRequestsAsDuplicate()
    {
        return $this->hasMany(PatientMergeRequest::class, 'duplicate_patient_id');
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
        return $query->where('status', 'active')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('merge_status')
                  ->orWhere('merge_status', '!=', 'MERGED');
            });
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
              })
              ->orWhereHas('aliases', function ($alias) use ($term) {
                  $normalized = PatientAlias::normalize($term);
                  $alias->where('alias_value', 'like', "%{$term}%")
                        ->orWhere('normalized_alias_value', 'like', "%{$normalized}%");
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

    public function isMerged(): bool
    {
        return $this->merge_status === 'MERGED' || $this->merged_to_patient_id !== null;
    }

    public function getFinalPatient(): self
    {
        $patient = $this;
        $seen = [];

        while ($patient->merged_to_patient_id && ! in_array($patient->id, $seen, true)) {
            $seen[] = $patient->id;
            $patient = $patient->mergedToPatient()->first() ?? $patient;
        }

        return $patient;
    }

    public function assertCanReceiveNewRecords(string $context = 'record'): void
    {
        if (! $this->isMerged()) {
            return;
        }

        $main = $this->getFinalPatient();
        $mainLabel = $main->id === $this->id
            ? 'the main patient folder'
            : "patient {$main->patient_number}";

        throw new \InvalidArgumentException("This patient folder has been merged. Create the {$context} under {$mainLabel} instead.");
    }
}

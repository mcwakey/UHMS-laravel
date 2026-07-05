<?php

namespace App\Services\Maternity;

use App\Enums\AdmissionStatus;
use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalVisitStatus;
use App\Enums\BedStatus;
use App\Enums\BleedingStatus;
use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\MaternalCondition;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornSex;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalMotherDangerSign;
use App\Enums\PostnatalObservationStatus;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\AntenatalVisit;
use App\Models\Bed;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
use App\Models\NewbornRecord;
use App\Models\Patient;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\PostnatalNewbornObservation;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MaternityManualTestDataService
{
    private const PREFIX = 'MT-MAT-';

    public function seed(int $count = 1, bool $freshManual = false, ?int $departmentId = null): array
    {
        if ($freshManual) {
            $this->clear();
        }

        $department = $departmentId ? Department::find($departmentId) : null;
        $department ??= Department::firstOrCreate(
            ['code' => 'MT-MAT'],
            ['name' => 'MT-MAT Manual Maternity', 'type' => DepartmentType::MATERNITY->value, 'status' => 'active']
        );

        $user = User::firstOrCreate(
            ['email' => 'mt-maternity@uhms.test'],
            [
                'first_name' => 'MT',
                'last_name' => 'Maternity',
                'phone' => '0200000000',
                'password' => Hash::make('password'),
                'department_id' => $department->id,
                'status' => 'active',
            ]
        );

        $created = ['profiles' => 0, 'anc_visits' => 0, 'labor_episodes' => 0, 'deliveries' => 0, 'newborns' => 0, 'postnatal_cases' => 0];

        for ($i = 1; $i <= max(1, $count); $i++) {
            $mother = Patient::factory()->create([
                'patient_number' => self::PREFIX.'P'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'first_name' => 'Manual',
                'last_name' => 'Maternity '.$i,
                'gender' => Gender::FEMALE,
                'registered_by' => $user->id,
                'phone' => '0244'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            ]);

            $visit = Visit::factory()->create([
                'patient_id' => $mother->id,
                'current_department_id' => $department->id,
                'created_by' => $user->id,
                'status' => VisitStatus::ACTIVE,
            ]);

            $admission = $this->admission($mother, $visit, $department, $user, $i);
            $profile = PregnancyProfile::create([
                'patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'department_id' => $department->id,
                'created_by' => $user->id,
                'gravida' => $i % 3 + 1,
                'para' => $i % 2,
                'last_menstrual_period' => now()->subWeeks(36 + ($i % 4))->toDateString(),
                'estimated_due_date' => now()->addWeeks($i % 4)->toDateString(),
                'gestational_age_weeks' => 36 + ($i % 4),
                'gestational_age_days' => $i % 7,
                'known_risks' => $i % 2 === 0 ? ['Manual high risk'] : null,
                'previous_caesarean' => $i % 4 === 0,
                'profile_status' => $i % 2 === 0 ? PregnancyProfileStatus::HIGH_RISK : PregnancyProfileStatus::ACTIVE,
            ]);
            $created['profiles']++;

            if ($i % 3 !== 0) {
                AntenatalVisit::create([
                    'pregnancy_profile_id' => $profile->id,
                    'patient_id' => $mother->id,
                    'visit_id' => $visit->id,
                    'admission_id' => $admission->id,
                    'department_id' => $department->id,
                    'recorded_by' => $user->id,
                    'visit_number' => 1,
                    'visit_date' => now()->subWeeks(2),
                    'gestational_age_weeks' => 34,
                    'blood_pressure_systolic' => $i % 2 === 0 ? 150 : 118,
                    'blood_pressure_diastolic' => $i % 2 === 0 ? 96 : 74,
                    'danger_signs' => $i % 2 === 0 ? ['severe_headache'] : [],
                    'risk_flags' => $i % 2 === 0 ? ['high_blood_pressure'] : [],
                    'assessment' => 'MT-MAT antenatal assessment',
                    'referral_type' => $i % 2 === 0 ? AntenatalReferralType::CONSULTATION : null,
                    'referral_reason' => $i % 2 === 0 ? 'MT-MAT danger sign referral' : null,
                    'next_visit_date' => now()->addWeek()->toDateString(),
                    'status' => $i % 2 === 0 ? AntenatalVisitStatus::HIGH_RISK : AntenatalVisitStatus::RECORDED,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $created['anc_visits']++;
            }

            $episode = LaborEpisode::create([
                'pregnancy_profile_id' => $profile->id,
                'patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'department_id' => $department->id,
                'started_by' => $user->id,
                'updated_by' => $user->id,
                'started_at' => now()->subHours(6),
                'labor_stage' => LaborStage::FIRST_STAGE,
                'status' => LaborEpisodeStatus::DELIVERED,
                'risk_level' => $i % 2 === 0 ? MaternityRiskLevel::HIGH : MaternityRiskLevel::LOW,
                'theatre_escalation_required' => $i % 5 === 0,
                'emergency_escalation_required' => $i % 7 === 0,
                'clinical_summary' => 'MT-MAT labor episode',
            ]);
            $created['labor_episodes']++;

            LaborObservation::create([
                'labor_episode_id' => $episode->id,
                'pregnancy_profile_id' => $profile->id,
                'patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'recorded_by' => $user->id,
                'observed_at' => now()->subHours(3),
                'labor_stage' => LaborStage::FIRST_STAGE,
                'fetal_heart_rate' => $i % 2 === 0 ? 175 : 142,
                'danger_signs' => $i % 2 === 0 ? ['abnormal_fetal_heart_rate'] : [],
                'risk_flags' => $i % 2 === 0 ? ['fetal_distress'] : [],
                'status' => 'recorded',
            ]);

            $delivery = DeliveryRecord::create([
                'labor_episode_id' => $episode->id,
                'pregnancy_profile_id' => $profile->id,
                'patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'department_id' => $department->id,
                'recorded_by' => $user->id,
                'delivery_at' => now()->subHour(),
                'delivery_mode' => $i % 5 === 0 ? DeliveryMode::CAESAREAN_SECTION : ($i % 4 === 0 ? DeliveryMode::ASSISTED_DELIVERY : DeliveryMode::SPONTANEOUS_VAGINAL_DELIVERY),
                'delivery_outcome' => $i % 6 === 0 ? DeliveryOutcome::STILLBIRTH : DeliveryOutcome::LIVE_BIRTH,
                'estimated_blood_loss_ml' => $i % 2 === 0 ? 650 : 250,
                'maternal_condition' => MaternalCondition::STABLE,
                'complications' => $i % 2 === 0 ? ['MT-MAT postpartum haemorrhage watch'] : [],
                'newborn_count' => $i % 4 === 0 ? 2 : 1,
                'newborn_records_pending' => false,
                'status' => DeliveryRecordStatus::COMPLETED,
            ]);
            $created['deliveries']++;

            for ($birthOrder = 1; $birthOrder <= (int) $delivery->newborn_count; $birthOrder++) {
                $stillbirth = $i % 6 === 0 && $birthOrder === 1;
                NewbornRecord::create([
                    'delivery_record_id' => $delivery->id,
                    'labor_episode_id' => $episode->id,
                    'pregnancy_profile_id' => $profile->id,
                    'mother_patient_id' => $mother->id,
                    'visit_id' => $visit->id,
                    'admission_id' => $admission->id,
                    'department_id' => $department->id,
                    'recorded_by' => $user->id,
                    'created_by' => $user->id,
                    'baby_number' => $birthOrder,
                    'birth_order' => $birthOrder,
                    'sex' => $birthOrder % 2 === 0 ? NewbornSex::FEMALE : NewbornSex::MALE,
                    'birth_time' => now()->subMinutes(45),
                    'birth_weight_kg' => $i % 3 === 0 ? 2.1 : 3.0,
                    'apgar_1_min' => $stillbirth ? 0 : 7,
                    'apgar_5_min' => $stillbirth ? 0 : ($i % 3 === 0 ? 5 : 8),
                    'resuscitation_required' => $i % 3 === 0,
                    'feeding_status' => $i % 3 === 0 ? NewbornFeedingStatus::DIFFICULTY : NewbornFeedingStatus::BREASTFEEDING,
                    'breathing_status' => $i % 3 === 0 ? NewbornBreathingStatus::DIFFICULTY : NewbornBreathingStatus::NORMAL,
                    'risk_flags' => $i % 3 === 0 ? ['low_birth_weight'] : [],
                    'outcome' => $stillbirth ? NewbornOutcome::STILLBIRTH : NewbornOutcome::LIVE_BIRTH,
                    'status' => $stillbirth ? NewbornRecordStatus::CLOSED : NewbornRecordStatus::STABLE,
                ]);
                $created['newborns']++;
            }

            $postnatal = PostnatalCase::create([
                'delivery_record_id' => $delivery->id,
                'labor_episode_id' => $episode->id,
                'pregnancy_profile_id' => $profile->id,
                'mother_patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'department_id' => $department->id,
                'opened_by' => $user->id,
                'updated_by' => $user->id,
                'opened_at' => now(),
                'status' => $i % 2 === 0 ? PostnatalCaseStatus::REFERRAL_REQUIRED : PostnatalCaseStatus::READY_FOR_DISCHARGE,
                'risk_level' => $i % 2 === 0 ? MaternityRiskLevel::HIGH : MaternityRiskLevel::LOW,
                'mother_ready_at' => $i % 2 === 0 ? null : now(),
                'newborn_ready_at' => $i % 2 === 0 ? null : now(),
                'ready_for_discharge_at' => $i % 2 === 0 ? null : now(),
                'referral_required' => $i % 2 === 0,
                'referral_reason' => $i % 2 === 0 ? 'MT-MAT postnatal warning' : null,
                'follow_up_date' => now()->addDays($i % 7)->toDateString(),
            ]);
            $created['postnatal_cases']++;

            PostnatalMotherObservation::create([
                'postnatal_case_id' => $postnatal->id,
                'delivery_record_id' => $delivery->id,
                'pregnancy_profile_id' => $profile->id,
                'mother_patient_id' => $mother->id,
                'visit_id' => $visit->id,
                'admission_id' => $admission->id,
                'department_id' => $department->id,
                'observed_by' => $user->id,
                'observed_at' => now(),
                'blood_pressure_systolic' => $i % 2 === 0 ? 148 : 116,
                'blood_pressure_diastolic' => $i % 2 === 0 ? 94 : 72,
                'bleeding_status' => $i % 2 === 0 ? BleedingStatus::HEAVY : BleedingStatus::NORMAL,
                'danger_signs' => $i % 2 === 0 ? [PostnatalMotherDangerSign::HEAVY_BLEEDING->value] : [],
                'status' => $i % 2 === 0 ? PostnatalObservationStatus::REVIEW_REQUIRED : PostnatalObservationStatus::RECORDED,
            ]);

            $newborn = $delivery->newbornRecords()->where('outcome', NewbornOutcome::LIVE_BIRTH->value)->first();
            if ($newborn) {
                PostnatalNewbornObservation::create([
                    'postnatal_case_id' => $postnatal->id,
                    'newborn_record_id' => $newborn->id,
                    'delivery_record_id' => $delivery->id,
                    'pregnancy_profile_id' => $profile->id,
                    'mother_patient_id' => $mother->id,
                    'visit_id' => $visit->id,
                    'admission_id' => $admission->id,
                    'department_id' => $department->id,
                    'observed_by' => $user->id,
                    'observed_at' => now(),
                    'temperature' => 36.8,
                    'weight_kg' => $newborn->birth_weight_kg,
                    'feeding_status' => $newborn->feeding_status?->value,
                    'breathing_status' => $newborn->breathing_status?->value,
                    'status' => PostnatalObservationStatus::RECORDED,
                ]);
            }
        }

        return $created;
    }

    public function clear(): int
    {
        $patientIds = Patient::where('patient_number', 'like', self::PREFIX.'%')->pluck('id');
        if ($patientIds->isEmpty()) {
            return 0;
        }

        $deleted = $patientIds->count();
        DB::transaction(function () use ($patientIds) {
            PostnatalNewbornObservation::whereIn('mother_patient_id', $patientIds)->delete();
            PostnatalMotherObservation::whereIn('mother_patient_id', $patientIds)->delete();
            PostnatalCase::whereIn('mother_patient_id', $patientIds)->delete();
            NewbornRecord::whereIn('mother_patient_id', $patientIds)->delete();
            DeliveryRecord::whereIn('patient_id', $patientIds)->delete();
            LaborObservation::whereIn('patient_id', $patientIds)->delete();
            LaborEpisode::whereIn('patient_id', $patientIds)->delete();
            AntenatalVisit::whereIn('patient_id', $patientIds)->delete();
            PregnancyProfile::whereIn('patient_id', $patientIds)->delete();
            Admission::whereIn('patient_id', $patientIds)->delete();
            Visit::whereIn('patient_id', $patientIds)->delete();
            Patient::whereIn('id', $patientIds)->delete();
        });

        return $deleted;
    }

    private function admission(Patient $mother, Visit $visit, Department $department, User $user, int $i): Admission
    {
        $ward = Ward::firstOrCreate(
            ['code' => 'MT-MAT-W'],
            ['name' => 'MT-MAT Ward', 'department_id' => $department->id, 'capacity' => 20, 'is_active' => true]
        );
        $bed = Bed::firstOrCreate(
            ['bed_number' => 'MT-MAT-'.$i],
            ['ward_id' => $ward->id, 'bed_type' => 'standard', 'status' => BedStatus::OCCUPIED->value, 'daily_rate' => 0]
        );

        return Admission::create([
            'admission_number' => self::PREFIX.'ADM'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'visit_id' => $visit->id,
            'patient_id' => $mother->id,
            'bed_id' => $bed->id,
            'admitted_by' => $user->id,
            'admitting_diagnosis' => 'MT-MAT maternity admission',
            'admission_date' => now()->subDay(),
            'status' => AdmissionStatus::ADMITTED,
            'admission_type' => 'maternity',
        ]);
    }
}

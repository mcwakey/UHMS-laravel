<?php

namespace App\Enums;

/**
 * The maternity record a consultation session can be linked to.
 *
 * Phase 14R.2 bridge. The set is intentionally closed: the link service
 * derives the context type from the target model, so arbitrary values can
 * never reach the database.
 */
enum ConsultationMaternityContextType: string
{
    case PREGNANCY_PROFILE = 'pregnancy_profile';
    case MATERNITY_CASE = 'maternity_case';
    case ANC_VISIT = 'anc_visit';
    case LABOR = 'labor';
    case DELIVERY = 'delivery';
    case NEWBORN = 'newborn';
    case POSTNATAL = 'postnatal';

    public function label(): string
    {
        return __('consultation_maternity.context_types.'.$this->value);
    }

    /**
     * The nullable foreign-key column that stores the target id for this type.
     */
    public function foreignKey(): string
    {
        return match ($this) {
            self::PREGNANCY_PROFILE => 'pregnancy_profile_id',
            self::MATERNITY_CASE => 'maternity_case_id',
            self::ANC_VISIT => 'antenatal_visit_id',
            self::LABOR => 'labor_episode_id',
            self::DELIVERY => 'delivery_record_id',
            self::NEWBORN => 'newborn_record_id',
            self::POSTNATAL => 'postnatal_case_id',
        };
    }

    /**
     * The maternity model class this context type points at.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::PREGNANCY_PROFILE => \App\Models\PregnancyProfile::class,
            self::MATERNITY_CASE => \App\Models\MaternityCase::class,
            self::ANC_VISIT => \App\Models\AntenatalVisit::class,
            self::LABOR => \App\Models\LaborEpisode::class,
            self::DELIVERY => \App\Models\DeliveryRecord::class,
            self::NEWBORN => \App\Models\NewbornRecord::class,
            self::POSTNATAL => \App\Models\PostnatalCase::class,
        };
    }

    /**
     * Resolve the context type for a maternity model instance, or null when
     * the model is not a supported bridge target.
     */
    public static function forModel(object $model): ?self
    {
        foreach (self::cases() as $case) {
            if ($model instanceof ($case->modelClass())) {
                return $case;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}

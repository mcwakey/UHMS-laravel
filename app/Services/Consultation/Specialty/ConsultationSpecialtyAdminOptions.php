<?php

namespace App\Services\Consultation\Specialty;

use App\Models\Drug;
use App\Models\IcdCode;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Product;
use App\Models\ServiceCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ConsultationSpecialtyAdminOptions
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:_[a-z0-9]+)*$/';

    public const FAVORITE_TYPES = [
        'diagnosis',
        'investigation',
        'procedure',
        'drug',
        'frequency',
        'task',
        'follow_up_instruction',
        'clinical_instruction',
    ];

    public const ITEM_TYPES = [
        'diagnosis',
        'investigation',
        'procedure',
        'drug',
        'prescription',
        'frequency',
        'task',
        'follow_up_instruction',
        'clinical_instruction',
        'specialty_entry_patch',
        'note',
    ];

    public const APPLY_MODES = [
        'suggest',
        'insert_text',
        'create_task',
        'patch_specialty_entry',
        'create_diagnosis_if_supported',
        'create_investigation_if_linked',
        'create_procedure_if_linked',
        'create_prescription_if_linked',
    ];

    public function billingContexts(): array
    {
        return ConsultationSpecialtyServiceMapping::CONTEXTS;
    }

    public function billingTriggers(): array
    {
        return ConsultationSpecialtyServiceMapping::TRIGGERS;
    }

    public function __construct(
        private readonly ConsultationSpecialtySectionComponentRegistry $components,
        private readonly ConsultationSpecialtySectionSchema $schemas,
    ) {}

    public function sectionKeys(): array
    {
        return collect($this->components->coreSectionKeys())
            ->merge($this->schemas->structuredSectionKeys())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function componentOptions(): array
    {
        return [
            'consultations.partials.specialty.core-section',
            'consultations.partials.specialty.structured-section',
            $this->components->fallbackComponent(),
        ];
    }

    public function allowedFavoritableTypes(): array
    {
        return collect([
            IcdCode::class,
            ServiceCatalog::class,
            Drug::class,
            Product::class,
        ])
            ->filter(fn (string $class) => class_exists($class))
            ->values()
            ->all();
    }

    public function safeModelExists(?string $type, mixed $id): bool
    {
        if (! $type && ! $id) {
            return true;
        }

        if (! $type || ! $id || ! in_array($type, $this->allowedFavoritableTypes(), true)) {
            return false;
        }

        return is_subclass_of($type, Model::class) && $type::query()->whereKey($id)->exists();
    }

    public function decodeJson(?string $value, string $field, array &$errors): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            $errors[$field] = __('consultation_specialties.admin.invalid_json');

            return null;
        }

        return $decoded;
    }

    public function fieldExists(string $sectionKey, ?string $field): bool
    {
        if ($field === null || $field === '') {
            return true;
        }

        return collect($this->schemas->fieldsFor($sectionKey))
            ->pluck('name')
            ->contains($field);
    }

    public function patchPayloadIsSafe(?array $payload): bool
    {
        return is_array(Arr::get($payload ?? [], 'merge'));
    }
}

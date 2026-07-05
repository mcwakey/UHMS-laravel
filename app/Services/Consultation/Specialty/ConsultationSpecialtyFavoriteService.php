<?php

namespace App\Services\Consultation\Specialty;

use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ClinicalFrequencyOptionService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConsultationSpecialtyFavoriteService
{
    public const TYPES = [
        ConsultationSpecialtyFavorite::TYPE_DIAGNOSIS,
        ConsultationSpecialtyFavorite::TYPE_INVESTIGATION,
        ConsultationSpecialtyFavorite::TYPE_PROCEDURE,
        ConsultationSpecialtyFavorite::TYPE_DRUG,
        ConsultationSpecialtyFavorite::TYPE_FREQUENCY,
        ConsultationSpecialtyFavorite::TYPE_TASK,
        ConsultationSpecialtyFavorite::TYPE_FOLLOW_UP_INSTRUCTION,
        ConsultationSpecialtyFavorite::TYPE_CLINICAL_INSTRUCTION,
    ];

    public function __construct(
        private readonly ClinicalFrequencyOptionService $frequencies,
    ) {}

    public function getFavoritesForProfile(ConsultationSpecialtyProfile $profile, string $type): EloquentCollection
    {
        if (! $profile->is_active) {
            return new EloquentCollection();
        }

        $favorites = ConsultationSpecialtyFavorite::query()
            ->with('favoritable')
            ->forProfile($profile)
            ->ofType($type)
            ->active()
            ->ordered()
            ->get()
            ->filter(fn (ConsultationSpecialtyFavorite $favorite) => $this->linkedModelIsUsable($favorite))
            ->values();

        return new EloquentCollection($favorites->all());
    }

    public function getFavoritesForResolvedContext(array|ResolvedConsultationSpecialty $context, string $type): EloquentCollection
    {
        $profile = $context instanceof ResolvedConsultationSpecialty
            ? $context->profile
            : ConsultationSpecialtyProfile::query()->find(data_get($context, 'profile.id'));

        return $profile instanceof ConsultationSpecialtyProfile
            ? $this->getFavoritesForProfile($profile, $type)
            : new EloquentCollection();
    }

    public function mergeFavoritesWithGlobalOptions(
        Collection|array $favorites,
        Collection|array $globalOptions,
        string $type,
        array $options = []
    ): array {
        $favoriteItems = collect($favorites)
            ->map(fn ($favorite) => $favorite instanceof ConsultationSpecialtyFavorite
                ? $this->favoriteToOption($favorite, $type, true)
                : $this->normalizeOption((array) $favorite, $type, true))
            ->filter();

        $globalItems = collect($globalOptions)
            ->map(fn ($option) => $this->normalizeOption($option, $type, false))
            ->filter();

        $seen = [];

        return $favoriteItems
            ->concat($globalItems)
            ->filter(function (array $option) use (&$seen): bool {
                $key = $this->dedupeKey($option);
                if (isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->values()
            ->all();
    }

    public function getWorkspaceDefaults(ConsultationSpecialtyProfile $profile): array
    {
        $defaults = [];

        foreach (self::TYPES as $type) {
            $favorites = $this->getFavoritesForProfile($profile, $type);
            $defaults[$type] = $favorites
                ->map(fn (ConsultationSpecialtyFavorite $favorite) => $this->favoriteToPayload($favorite))
                ->values()
                ->all();
        }

        $defaults['frequency_defaults'] = $this->mergeFavoritesWithGlobalOptions(
            $this->getFavoritesForProfile($profile, ConsultationSpecialtyFavorite::TYPE_FREQUENCY),
            $this->frequencies->options(),
            ConsultationSpecialtyFavorite::TYPE_FREQUENCY,
        );

        return $defaults;
    }

    public function favoriteToPayload(ConsultationSpecialtyFavorite $favorite): array
    {
        return [
            'id' => $favorite->id,
            'type' => $favorite->favorite_type,
            'label' => $favorite->label,
            'code' => $favorite->code,
            'favoritable_type' => $favorite->favoritable_type,
            'favoritable_id' => $favorite->favoritable_id,
            'metadata' => $favorite->metadata ?? [],
        ];
    }

    public function standardFrequencyOptions(): array
    {
        return $this->frequencies->options();
    }

    private function favoriteToOption(ConsultationSpecialtyFavorite $favorite, string $type, bool $isFavorite): ?array
    {
        return $this->normalizeOption([
            'id' => $favorite->favoritable_id,
            'value' => $favorite->code ?: $favorite->favoritable_id,
            'label' => $favorite->label,
            'code' => $favorite->code,
            'favoritable_type' => $favorite->favoritable_type,
            'favoritable_id' => $favorite->favoritable_id,
            'metadata' => $favorite->metadata ?? [],
        ], $type, $isFavorite);
    }

    private function normalizeOption(mixed $option, string $type, bool $isFavorite): ?array
    {
        if (is_object($option) && method_exists($option, 'getKey')) {
            $label = $option->name ?? $option->description ?? $option->label ?? null;

            return $label ? [
                'id' => $option->getKey(),
                'value' => $option->getKey(),
                'label' => $label,
                'code' => $option->code ?? null,
                'type' => $type,
                'is_favorite' => $isFavorite,
            ] : null;
        }

        $array = (array) $option;
        $label = $array['label'] ?? $array['name'] ?? $array['description'] ?? null;
        $value = $array['value'] ?? $array['code'] ?? $array['id'] ?? $label;

        if (! $label || $value === null || $value === '') {
            return null;
        }

        return array_filter([
            'id' => $array['id'] ?? null,
            'value' => (string) $value,
            'label' => (string) $label,
            'code' => $array['code'] ?? null,
            'type' => $type,
            'favoritable_type' => $array['favoritable_type'] ?? null,
            'favoritable_id' => $array['favoritable_id'] ?? null,
            'metadata' => $array['metadata'] ?? [],
            'doses_per_day' => $array['doses_per_day'] ?? null,
            'task_instances' => $array['task_instances'] ?? null,
            'is_prn' => $array['is_prn'] ?? null,
            'is_favorite' => $isFavorite,
        ], fn ($value) => $value !== null);
    }

    private function dedupeKey(array $option): string
    {
        if (! empty($option['favoritable_type']) && ! empty($option['favoritable_id'])) {
            return 'model:'.$option['favoritable_type'].':'.$option['favoritable_id'];
        }

        if (! empty($option['id']) && ! empty($option['favoritable_type'])) {
            return 'model:'.$option['favoritable_type'].':'.$option['id'];
        }

        $code = Str::lower(trim((string) ($option['code'] ?? $option['value'] ?? '')));
        if ($code !== '') {
            return 'code:'.$code;
        }

        return 'label:'.Str::lower(trim((string) ($option['label'] ?? '')));
    }

    private function linkedModelIsUsable(ConsultationSpecialtyFavorite $favorite): bool
    {
        if (! $favorite->favoritable_type || ! $favorite->favoritable_id) {
            return true;
        }

        return $favorite->favoritable !== null;
    }
}

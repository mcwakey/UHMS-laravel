<?php

namespace App\Services;

use App\Models\ComplaintCatalogue;

class ComplaintCatalogueService
{
    public function query(array $filters = [])
    {
        return ComplaintCatalogue::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $like = '%'.$search.'%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('body_system', 'like', $like)
                        ->orWhere('keywords', 'like', $like);
                });
            })
            ->when(($filters['category'] ?? null) !== null && ($filters['category'] ?? '') !== '', fn ($query) => $query->where('category', $filters['category']))
            ->when(($filters['active'] ?? null) !== null && ($filters['active'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['active']))
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name');
    }

    public function create(array $data): ComplaintCatalogue
    {
        $data['keywords'] = $this->normaliseKeywords($data['keywords'] ?? null);

        return ComplaintCatalogue::create($data);
    }

    public function update(ComplaintCatalogue $complaint, array $data): ComplaintCatalogue
    {
        if (array_key_exists('keywords', $data)) {
            $data['keywords'] = $this->normaliseKeywords($data['keywords']);
        }

        $complaint->update($data);

        return $complaint->fresh();
    }

    public function toggle(ComplaintCatalogue $complaint): ComplaintCatalogue
    {
        $complaint->update(['is_active' => ! $complaint->is_active]);

        return $complaint->fresh();
    }

    public function normaliseKeywords(mixed $keywords): array
    {
        if (is_array($keywords)) {
            $items = $keywords;
        } else {
            $items = preg_split('/[,\n]/', (string) $keywords) ?: [];
        }

        return collect($items)
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique(fn ($keyword) => mb_strtolower($keyword))
            ->values()
            ->all();
    }
}
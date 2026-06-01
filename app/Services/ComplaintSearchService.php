<?php

namespace App\Services;

use App\Models\ComplaintCatalogue;
use Illuminate\Support\Collection;

class ComplaintSearchService
{
    public function search(?string $term, int $limit = 15): Collection
    {
        $term = trim((string) $term);
        $limit = max(1, min($limit, 30));

        return ComplaintCatalogue::query()
            ->active()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('body_system', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('keywords', 'like', $like);
                });
            })
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'category', 'body_system', 'description', 'keywords']);
    }

    public function autocompletePayload(Collection $complaints): array
    {
        return $complaints->map(fn (ComplaintCatalogue $complaint) => [
            'id' => $complaint->id,
            'name' => $complaint->name,
            'category' => $complaint->category,
            'body_system' => $complaint->body_system,
            'description' => $complaint->description,
            'keywords' => $complaint->keywords ?: [],
        ])->values()->all();
    }
}
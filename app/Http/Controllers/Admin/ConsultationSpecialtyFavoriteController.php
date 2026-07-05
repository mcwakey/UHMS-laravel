<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyFavoriteController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options)
    {
        $favorites = $profile->favorites()
            ->when($request->filled('favorite_type'), fn ($query) => $query->where('favorite_type', $request->string('favorite_type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('linked'), fn ($query) => $request->linked === 'linked' ? $query->whereNotNull('favoritable_type') : $query->whereNull('favoritable_type'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($inner) => $inner
                    ->where('label', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('search_terms', 'like', "%{$search}%"));
            })
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.favorites', [
            'profile' => $profile,
            'favorites' => $favorites,
            'types' => ConsultationSpecialtyAdminOptions::FAVORITE_TYPES,
            'favoritableTypes' => $options->allowedFavoritableTypes(),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options)
    {
        $favorite = $profile->favorites()->create($this->validated($request, $options));
        $this->log('FAVORITE_CREATED', $favorite);

        return back()->with('success', __('consultation_specialties.admin.saved'));
    }

    public function update(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyFavorite $favorite, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOwnership($profile, $favorite);
        $favorite->update($this->validated($request, $options));
        $this->log('FAVORITE_UPDATED', $favorite);

        return back()->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyFavorite $favorite)
    {
        $this->authorizeOwnership($profile, $favorite);
        $this->log('FAVORITE_DELETED', $favorite);
        $favorite->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request, ConsultationSpecialtyProfile $profile)
    {
        $data = $request->validate(['orders' => ['required', 'array'], 'orders.*' => ['integer', 'min:0']]);
        foreach ($data['orders'] as $id => $order) {
            $profile->favorites()->whereKey($id)->update(['sort_order' => $order]);
        }
        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_FAVORITES_REORDERED', ['profile_id' => $profile->id]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyAdminOptions $options): array
    {
        $data = $request->validate([
            'favorite_type' => ['required', Rule::in(ConsultationSpecialtyAdminOptions::FAVORITE_TYPES)],
            'favoritable_type' => ['nullable', 'string', Rule::in($options->allowedFavoritableTypes())],
            'favoritable_id' => ['nullable', 'integer'],
            'code' => ['nullable', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'search_terms' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        if (! $options->safeModelExists($data['favoritable_type'] ?? null, $data['favoritable_id'] ?? null)) {
            back()->withErrors(['favoritable_type' => __('consultation_specialties.admin.unsafe_model_type')])->withInput()->throwResponse();
        }

        if (blank($data['favoritable_type'] ?? null)) {
            $data['favoritable_type'] = null;
            $data['favoritable_id'] = null;
        }

        $errors = [];
        $data['metadata'] = $options->decodeJson($data['metadata_json'] ?? null, 'metadata_json', $errors);
        unset($data['metadata_json']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function authorizeOwnership(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyFavorite $favorite): void
    {
        abort_unless((int) $favorite->consultation_specialty_profile_id === (int) $profile->id, 404);
    }

    private function log(string $event, ConsultationSpecialtyFavorite $favorite): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'profile_id' => $favorite->consultation_specialty_profile_id,
            'favorite_type' => $favorite->favorite_type,
            'label' => $favorite->label,
        ], $favorite, 'Consultation specialty favorite configuration changed.');
    }
}

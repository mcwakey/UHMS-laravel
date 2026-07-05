<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyOrderSetController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request, ConsultationSpecialtyProfile $profile)
    {
        $orderSets = $profile->orderSets()
            ->withCount(['items', 'applications'])
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.order-sets.index', compact('profile', 'orderSets'));
    }

    public function create(ConsultationSpecialtyProfile $profile)
    {
        return view('admin.consultation-specialties.order-sets.form', [
            'profile' => $profile,
            'orderSet' => new ConsultationSpecialtyOrderSet(['is_active' => true]),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options)
    {
        $orderSet = $profile->orderSets()->create($this->validated($request, $profile, $options));
        $this->log('ORDER_SET_CREATED', $orderSet);

        return redirect()->route('admin.consultation-specialties.order-sets.show', [$profile, $orderSet])
            ->with('success', __('consultation_specialties.admin.saved'));
    }

    public function show(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOwnership($profile, $orderSet);
        $orderSet->load(['items' => fn ($query) => $query->ordered()]);
        $orderSet->loadCount('applications');

        return view('admin.consultation-specialties.order-sets.show', [
            'profile' => $profile,
            'orderSet' => $orderSet,
            'itemTypes' => ConsultationSpecialtyAdminOptions::ITEM_TYPES,
            'applyModes' => ConsultationSpecialtyAdminOptions::APPLY_MODES,
            'sectionKeys' => $profile->sections()->ordered()->pluck('section_key')->all(),
            'favoritableTypes' => $options->allowedFavoritableTypes(),
        ]);
    }

    public function edit(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet)
    {
        $this->authorizeOwnership($profile, $orderSet);

        return view('admin.consultation-specialties.order-sets.form', compact('profile', 'orderSet'));
    }

    public function update(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOwnership($profile, $orderSet);
        $orderSet->update($this->validated($request, $profile, $options, $orderSet));
        $this->log('ORDER_SET_UPDATED', $orderSet);

        return redirect()->route('admin.consultation-specialties.order-sets.show', [$profile, $orderSet])
            ->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet)
    {
        $this->authorizeOwnership($profile, $orderSet);

        if ($orderSet->applications()->exists()) {
            $orderSet->update(['is_active' => false]);
            $this->log('ORDER_SET_DEACTIVATED', $orderSet);

            return back()->with('success', __('consultation_specialties.admin.deactivated'));
        }

        $this->log('ORDER_SET_DELETED', $orderSet);
        $orderSet->delete();

        return redirect()->route('admin.consultation-specialties.order-sets.index', $profile)
            ->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request, ConsultationSpecialtyProfile $profile)
    {
        $data = $request->validate(['orders' => ['required', 'array'], 'orders.*' => ['integer', 'min:0']]);
        foreach ($data['orders'] as $id => $order) {
            $profile->orderSets()->whereKey($id)->update(['sort_order' => $order]);
        }
        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_ORDER_SETS_REORDERED', ['profile_id' => $profile->id]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options, ?ConsultationSpecialtyOrderSet $orderSet = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN, Rule::unique('consultation_specialty_order_sets', 'code')->where('consultation_specialty_profile_id', $profile->id)->ignore($orderSet)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        $errors = [];
        $data['metadata'] = $options->decodeJson($data['metadata_json'] ?? null, 'metadata_json', $errors);
        unset($data['metadata_json']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function authorizeOwnership(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyOrderSet $orderSet): void
    {
        abort_unless((int) $orderSet->consultation_specialty_profile_id === (int) $profile->id, 404);
    }

    private function log(string $event, ConsultationSpecialtyOrderSet $orderSet): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'profile_id' => $orderSet->consultation_specialty_profile_id,
            'order_set_id' => $orderSet->id,
            'code' => $orderSet->code,
        ], $orderSet, 'Consultation specialty order set configuration changed.');
    }
}

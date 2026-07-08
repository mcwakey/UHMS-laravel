<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyProfile;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionAliasService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyProfileController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request)
    {
        $profiles = ConsultationSpecialtyProfile::query()
            ->withCount(['sections', 'favorites', 'orderSets', 'mappings'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.index', [
            'profiles' => $profiles,
            'departmentTypes' => DepartmentType::cases(),
        ]);
    }

    public function create()
    {
        return view('admin.consultation-specialties.form', [
            'profile' => new ConsultationSpecialtyProfile(['is_active' => true]),
            'departmentTypes' => DepartmentType::cases(),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyAdminOptions $options)
    {
        $data = $this->validated($request, $options);
        $profile = ConsultationSpecialtyProfile::query()->create($data);
        $this->log('PROFILE_CREATED', $profile);

        return redirect()->route('admin.consultation-specialties.show', $profile)
            ->with('success', __('consultation_specialties.admin.saved'));
    }

    public function show(ConsultationSpecialtyProfile $profile, ConsultationSpecialtySectionAliasService $sectionAliases)
    {
        $profile->loadCount(['sections', 'favorites', 'orderSets', 'mappings', 'doctorPreferences']);
        $profile->load([
            'sections' => fn ($query) => $query->ordered(),
            'favorites' => fn ($query) => $query->ordered()->limit(8),
            'orderSets' => fn ($query) => $query->ordered()->withCount('items')->limit(8),
            'mappings' => fn ($query) => $query->ordered()->with('department')->limit(8),
        ]);

        return view('admin.consultation-specialties.show', [
            'profile' => $profile,
            'sectionAliasMap' => $sectionAliases->aliasMapFor($profile->code),
            'complaintDisplayLabel' => $sectionAliases->displayLabelFor($profile->code, 'complaints'),
        ]);
    }

    public function edit(ConsultationSpecialtyProfile $profile)
    {
        return view('admin.consultation-specialties.form', [
            'profile' => $profile,
            'departmentTypes' => DepartmentType::cases(),
        ]);
    }

    public function update(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options)
    {
        $data = $this->validated($request, $options, $profile);

        if ($profile->isGeneral()) {
            $data['code'] = ConsultationSpecialtyProfile::GENERAL_MEDICINE;
            $data['is_active'] = true;
        }

        $profile->update($data);
        $this->log('PROFILE_UPDATED', $profile);

        return redirect()->route('admin.consultation-specialties.show', $profile)
            ->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfile $profile)
    {
        if ($profile->isGeneral()) {
            return back()->with('error', __('consultation_specialties.admin.general_profile_locked'));
        }

        if ($profile->entries()->exists() || ConsultationSpecialtyOrderSetApplication::query()->where('consultation_specialty_profile_id', $profile->id)->exists()) {
            $profile->update(['is_active' => false]);
            $this->log('PROFILE_DEACTIVATED', $profile);

            return back()->with('success', __('consultation_specialties.admin.deactivated'));
        }

        $this->log('PROFILE_DELETED', $profile);
        $profile->delete();

        return redirect()->route('admin.consultation-specialties.index')
            ->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['integer', 'min:0'],
        ]);

        foreach ($data['orders'] as $id => $order) {
            ConsultationSpecialtyProfile::query()->whereKey($id)->update(['sort_order' => $order]);
        }

        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_PROFILES_REORDERED', ['count' => count($data['orders'])]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyAdminOptions $options, ?ConsultationSpecialtyProfile $profile = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN, Rule::unique('consultation_specialty_profiles', 'code')->ignore($profile)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_type' => ['nullable', 'string', Rule::in(collect(DepartmentType::cases())->pluck('value')->all())],
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

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function log(string $event, ConsultationSpecialtyProfile $profile): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'profile_id' => $profile->id,
            'code' => $profile->code,
        ], $profile, 'Consultation specialty profile configuration changed.');
    }
}

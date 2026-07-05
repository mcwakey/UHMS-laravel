<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyMappingController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request)
    {
        $mappings = ConsultationSpecialtyProfileMapping::query()
            ->with(['profile', 'department', 'user'])
            ->when($request->filled('profile_id'), fn ($query) => $query->where('consultation_specialty_profile_id', $request->integer('profile_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('department_type'), fn ($query) => $query->where('department_type', $request->string('department_type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.mappings', [
            'mappings' => $mappings,
            'profiles' => ConsultationSpecialtyProfile::query()->ordered()->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'departmentTypes' => DepartmentType::cases(),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyAdminOptions $options)
    {
        $mapping = ConsultationSpecialtyProfileMapping::query()->create($this->validated($request, $options));
        $this->log('MAPPING_CREATED', $mapping);

        return back()->with('success', __('consultation_specialties.admin.saved'));
    }

    public function update(Request $request, ConsultationSpecialtyProfileMapping $mapping, ConsultationSpecialtyAdminOptions $options)
    {
        $mapping->update($this->validated($request, $options));
        $this->log('MAPPING_UPDATED', $mapping);

        return back()->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfileMapping $mapping)
    {
        $this->log('MAPPING_DELETED', $mapping);
        $mapping->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }

    private function validated(Request $request, ConsultationSpecialtyAdminOptions $options): array
    {
        $data = $request->validate([
            'consultation_specialty_profile_id' => ['required', 'exists:consultation_specialty_profiles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'consultation_route_id' => ['nullable', 'exists:visit_consultation_routes,id'],
            'department_type' => ['nullable', 'string', Rule::in(collect(DepartmentType::cases())->pluck('value')->all())],
            'user_id' => ['nullable', 'exists:users,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        if (! collect(['department_id', 'consultation_route_id', 'department_type', 'user_id'])->contains(fn ($field) => filled($data[$field] ?? null))) {
            back()->withErrors(['mapping_target' => __('consultation_specialties.admin.mapping_target_required')])->withInput()->throwResponse();
        }

        $errors = [];
        $data['metadata'] = $options->decodeJson($data['metadata_json'] ?? null, 'metadata_json', $errors);
        unset($data['metadata_json']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        $data['priority'] = (int) ($data['priority'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function log(string $event, ConsultationSpecialtyProfileMapping $mapping): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'mapping_id' => $mapping->id,
            'profile_id' => $mapping->consultation_specialty_profile_id,
        ], $mapping, 'Consultation specialty mapping configuration changed.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtyServiceMappingController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request, ConsultationSpecialtyAdminOptions $options)
    {
        $mappings = ConsultationSpecialtyServiceMapping::query()
            ->with(['profile', 'service', 'department'])
            ->when($request->filled('profile_id'), fn ($query) => $query->where('consultation_specialty_profile_id', $request->integer('profile_id')))
            ->when($request->filled('mapping_context'), fn ($query) => $query->where('mapping_context', $request->string('mapping_context')))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->integer('service_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('auto_bill'), fn ($query) => $query->where('auto_bill', $request->auto_bill === 'yes'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.service-mappings', [
            'mappings' => $mappings,
            'profiles' => ConsultationSpecialtyProfile::query()->ordered()->get(),
            'services' => ServiceCatalog::query()->where('is_active', true)->orderBy('name')->limit(500)->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'departmentTypes' => DepartmentType::cases(),
            'contexts' => $options->billingContexts(),
            'triggers' => $options->billingTriggers(),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyAdminOptions $options)
    {
        $mapping = ConsultationSpecialtyServiceMapping::query()->create($this->validated($request, $options));
        $this->log('SERVICE_MAPPING_CREATED', $mapping);

        return back()->with('success', __('consultation_specialties.admin.saved'));
    }

    public function update(Request $request, ConsultationSpecialtyServiceMapping $mapping, ConsultationSpecialtyAdminOptions $options)
    {
        $mapping->update($this->validated($request, $options, $mapping));
        $this->log('SERVICE_MAPPING_UPDATED', $mapping);

        return back()->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyServiceMapping $mapping)
    {
        $this->log('SERVICE_MAPPING_DELETED', $mapping);
        $mapping->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['orders' => ['required', 'array'], 'orders.*' => ['integer', 'min:0']]);
        foreach ($data['orders'] as $id => $order) {
            ConsultationSpecialtyServiceMapping::query()->whereKey($id)->update(['priority' => $order]);
        }
        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_SERVICE_MAPPINGS_REORDERED', ['count' => count($data['orders'])]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyAdminOptions $options, ?ConsultationSpecialtyServiceMapping $mapping = null): array
    {
        $data = $request->validate([
            'consultation_specialty_profile_id' => ['required', 'exists:consultation_specialty_profiles,id'],
            'service_id' => ['required', Rule::exists('service_catalog', 'id')->where('is_active', true)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'consultation_route_id' => ['nullable', 'integer'],
            'department_type' => ['nullable', 'string', Rule::in(collect(DepartmentType::cases())->pluck('value')->all())],
            'section_key' => ['nullable', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN],
            'mapping_context' => ['required', Rule::in($options->billingContexts())],
            'billing_trigger' => ['required', Rule::in($options->billingTriggers())],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'auto_bill' => ['nullable', 'boolean'],
            'auto_bill_acknowledged' => ['nullable', 'boolean'],
            'requires_confirmation' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata_json' => ['nullable', 'string'],
        ]);

        if ($request->boolean('auto_bill') && ! $request->boolean('auto_bill_acknowledged')) {
            back()->withErrors(['auto_bill_acknowledged' => __('consultation_specialties.billing.admin.auto_bill_warning')])->withInput()->throwResponse();
        }

        if ($request->boolean('is_default') && $request->boolean('is_active')) {
            $conflict = ConsultationSpecialtyServiceMapping::query()
                ->where('consultation_specialty_profile_id', $data['consultation_specialty_profile_id'])
                ->where('mapping_context', $data['mapping_context'])
                ->where('is_default', true)
                ->where('is_active', true)
                ->whereNull('department_id')
                ->whereNull('department_type')
                ->whereNull('consultation_route_id')
                ->when($mapping, fn ($query) => $query->whereKeyNot($mapping->id))
                ->exists();

            if ($conflict && blank($data['department_id'] ?? null) && blank($data['department_type'] ?? null) && blank($data['consultation_route_id'] ?? null)) {
                back()->withErrors(['is_default' => __('consultation_specialties.billing.admin.active_default_conflict')])->withInput()->throwResponse();
            }
        }

        $errors = [];
        $data['metadata'] = $options->decodeJson($data['metadata_json'] ?? null, 'metadata_json', $errors);
        unset($data['metadata_json'], $data['auto_bill_acknowledged']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        $data['priority'] = (int) ($data['priority'] ?? 0);
        $data['is_default'] = $request->boolean('is_default');
        $data['auto_bill'] = $request->boolean('auto_bill');
        $data['requires_confirmation'] = $request->boolean('requires_confirmation', true);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function log(string $event, ConsultationSpecialtyServiceMapping $mapping): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'mapping_id' => $mapping->id,
            'profile_id' => $mapping->consultation_specialty_profile_id,
            'service_id' => $mapping->service_id,
            'context' => $mapping->mapping_context,
        ], $mapping, 'Consultation specialty service mapping configuration changed.');
    }
}

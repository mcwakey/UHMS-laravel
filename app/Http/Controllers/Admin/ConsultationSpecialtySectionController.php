<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtySection;
use App\Services\ActivityLogService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyAdminOptions;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionAliasService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationSpecialtySectionController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options, ConsultationSpecialtySectionAliasService $sectionAliases)
    {
        return view('admin.consultation-specialties.sections', [
            'profile' => $profile->load(['sections' => fn ($query) => $query->ordered()]),
            'sectionKeys' => $options->sectionKeys(),
            'components' => $options->componentOptions(),
            'sectionAliasMap' => $sectionAliases->aliasMapFor($profile->code),
            'complaintDisplayLabel' => $sectionAliases->displayLabelFor($profile->code, 'complaints'),
        ]);
    }

    public function store(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options)
    {
        $data = $this->validated($request, $profile, $options);
        $section = $profile->sections()->create($data);
        $this->log('SECTION_CREATED', $section);

        return back()->with('success', __('consultation_specialties.admin.saved'));
    }

    public function update(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtySection $section, ConsultationSpecialtyAdminOptions $options)
    {
        $this->authorizeOwnership($profile, $section);
        $data = $this->validated($request, $profile, $options, $section);
        $section->update($data);
        $this->log('SECTION_UPDATED', $section);

        return back()->with('success', __('consultation_specialties.admin.updated'));
    }

    public function destroy(ConsultationSpecialtyProfile $profile, ConsultationSpecialtySection $section)
    {
        $this->authorizeOwnership($profile, $section);

        if ($profile->isGeneral() && $section->is_visible && $profile->sections()->visible()->count() <= 1) {
            return back()->with('error', __('consultation_specialties.admin.general_profile_locked'));
        }

        $this->log('SECTION_DELETED', $section);
        $section->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }

    public function reorder(Request $request, ConsultationSpecialtyProfile $profile)
    {
        $data = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['integer', 'min:0'],
        ]);

        foreach ($data['orders'] as $id => $order) {
            $profile->sections()->whereKey($id)->update(['display_order' => $order]);
        }

        $this->activity->log(LogModule::CONSULTATION, 'SPECIALTY_SECTIONS_REORDERED', ['profile_id' => $profile->id]);

        return back()->with('success', __('consultation_specialties.admin.reordered'));
    }

    private function validated(Request $request, ConsultationSpecialtyProfile $profile, ConsultationSpecialtyAdminOptions $options, ?ConsultationSpecialtySection $section = null): array
    {
        $data = $request->validate([
            'section_key' => ['required', 'string', 'max:100', 'regex:'.ConsultationSpecialtyAdminOptions::SLUG_PATTERN, Rule::unique('consultation_specialty_sections', 'section_key')->where('consultation_specialty_profile_id', $profile->id)->ignore($section)],
            'label' => ['required', 'string', 'max:255'],
            'component' => ['nullable', 'string', Rule::in($options->componentOptions())],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'config_json' => ['nullable', 'string'],
        ]);

        $errors = [];
        $data['config'] = $options->decodeJson($data['config_json'] ?? null, 'config_json', $errors);
        unset($data['config_json']);

        if ($errors) {
            back()->withErrors($errors)->withInput()->throwResponse();
        }

        $data['display_order'] = (int) ($data['display_order'] ?? 0);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_visible'] = $request->boolean('is_visible', true);

        return $data;
    }

    private function authorizeOwnership(ConsultationSpecialtyProfile $profile, ConsultationSpecialtySection $section): void
    {
        abort_unless((int) $section->consultation_specialty_profile_id === (int) $profile->id, 404);
    }

    private function log(string $event, ConsultationSpecialtySection $section): void
    {
        $this->activity->log(LogModule::CONSULTATION, $event, [
            'profile_id' => $section->consultation_specialty_profile_id,
            'section_key' => $section->section_key,
        ], $section, 'Consultation specialty section configuration changed.');
    }
}

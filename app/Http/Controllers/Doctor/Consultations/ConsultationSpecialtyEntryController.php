<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Models\Visit;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtySectionSchema;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConsultationSpecialtyEntryController extends ConsultationWorkflowController
{
    public function store(
        Request $request,
        Visit $visit,
        string $sectionKey,
        ConsultationSpecialtyEntryService $entries,
        ConsultationSpecialtyProfileResolver $resolver,
        ConsultationSpecialtySectionSchema $schemas,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_entry.upsert', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve(
            user: $request->user(),
            visit: $visit,
            consultationRoute: $context->route,
            department: $context->route?->department,
        );

        $profileId = $request->integer('specialty_profile_id');
        if ($profileId && (int) $resolved->profile->id !== $profileId) {
            throw ValidationException::withMessages([
                'specialty_profile_id' => __('consultation_specialties.messages.profile_mismatch'),
            ]);
        }

        $allowedSections = collect($resolved->profile->activeSections()->pluck('section_key'));
        if (! $allowedSections->contains($sectionKey) || ! $schemas->hasSchema($sectionKey)) {
            throw ValidationException::withMessages([
                'section_key' => __('consultation_specialties.messages.invalid_section'),
            ]);
        }

        if (! $resolved->profile->is_active) {
            throw ValidationException::withMessages([
                'specialty_profile_id' => __('consultation_specialties.messages.inactive_profile'),
            ]);
        }

        foreach ($schemas->fieldsFor($sectionKey) as $field) {
            if (($field['type'] ?? null) === 'array' && is_array($request->input($field['name']))) {
                $request->merge([
                    $field['name'] => collect($request->input($field['name']))
                        ->flatMap(fn ($value) => preg_split('/\R+/', (string) $value) ?: [])
                        ->map(fn ($value) => trim((string) $value))
                        ->filter()
                        ->values()
                        ->all(),
                ]);
            }
        }

        $validated = $request->validate($schemas->rulesFor($sectionKey));
        $entry = $schemas->sanitizedEntry($sectionKey, $validated);

        $entryId = $request->integer('consultation_specialty_entry_id');
        $existing = $entryId
            ? $entries->getEntryById($context->route, $resolved->profile, $sectionKey, $entryId)
            : null;

        if ($entryId && ! $existing) {
            throw ValidationException::withMessages([
                'consultation_specialty_entry_id' => __('consultation_specialties.messages.invalid_section'),
            ]);
        }

        if ($entry === []) {
            if ($existing) {
                $entries->deleteEntry($existing, $request->user());
            }

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => __('consultation_specialties.messages.section_deleted'),
                    'entry' => null,
                ]);
            }

            return back()
                ->withFragment('specialty-'.$sectionKey.'-section')
                ->with('success', __('consultation_specialties.messages.section_deleted'));
        }

        $model = $existing
            ? $entries->updateEntry($existing, $entry, $request->user())
            : $entries->createEntry($context->route, $resolved->profile, $sectionKey, $entry, $request->user());

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('consultation_specialties.messages.section_saved'),
                'entry' => $model->fresh(),
            ]);
        }

        return back()
            ->withFragment('specialty-'.$sectionKey.'-section')
            ->with('success', __('consultation_specialties.messages.section_saved'));
    }

    public function destroy(
        Request $request,
        Visit $visit,
        string $sectionKey,
        ConsultationSpecialtyEntryService $entries,
        ConsultationSpecialtyProfileResolver $resolver,
    ) {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'specialty_entry.delete', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $resolved = $resolver->resolve($request->user(), visit: $visit, consultationRoute: $context->route, department: $context->route?->department);
        $entryId = $request->integer('consultation_specialty_entry_id');
        $entry = $entryId
            ? $entries->getEntryById($context->route, $resolved->profile, $sectionKey, $entryId)
            : $entries->getEntry($context->route, $resolved->profile, $sectionKey);

        if ($entry) {
            $entries->deleteEntry($entry, $request->user());
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true]);
        }

        return back()
            ->withFragment('specialty-'.$sectionKey.'-section')
            ->with('success', __('consultation_specialties.messages.section_deleted'));
    }
}

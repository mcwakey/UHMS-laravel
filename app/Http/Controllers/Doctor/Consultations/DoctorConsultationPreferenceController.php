<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Controller;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtyQuickActionRegistry;
use App\Services\Consultation\Specialty\DoctorConsultationPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorConsultationPreferenceController extends Controller
{
    public function updatePinnedActions(
        Request $request,
        DoctorConsultationPreferenceService $preferences,
        ConsultationSpecialtyProfileResolver $resolver,
        ConsultationSpecialtyQuickActionRegistry $actions,
    ) {
        $profile = $resolver->resolve($request->user())->profile;
        $validKeys = $actions->validKeysForProfile($profile);

        $data = $request->validate([
            'pinned_actions' => ['nullable', 'array', 'max:12'],
            'pinned_actions.*' => ['string', 'max:100', Rule::in($validKeys)],
        ]);

        $preference = $preferences->updatePinnedActions($request->user(), $data['pinned_actions'] ?? []);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'preferences' => $preference]);
        }

        return back()->with('success', __('consultation_specialties.workspace.preferences_saved'));
    }

    public function updateLayout(Request $request, DoctorConsultationPreferenceService $preferences)
    {
        $data = $request->validate([
            'preferred_layout' => ['nullable', Rule::in(['default', 'compact', 'expanded'])],
            'compact_mode' => ['nullable', 'boolean'],
        ]);

        $preference = $preferences->updateLayoutPreference(
            $request->user(),
            $data['preferred_layout'] ?? 'default',
            $request->boolean('compact_mode'),
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'preferences' => $preference]);
        }

        return back()->with('success', __('consultation_specialties.workspace.preferences_saved'));
    }
}

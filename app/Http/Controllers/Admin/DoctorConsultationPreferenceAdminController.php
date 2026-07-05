<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\DoctorConsultationPreference;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class DoctorConsultationPreferenceAdminController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request)
    {
        $preferences = DoctorConsultationPreference::query()
            ->with(['user', 'defaultSpecialtyProfile', 'defaultDepartment'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->whereHas('user', fn ($userQuery) => $userQuery
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultation-specialties.doctor-preferences', compact('preferences'));
    }

    public function destroy(DoctorConsultationPreference $preference)
    {
        $this->activity->log(LogModule::CONSULTATION, 'DOCTOR_CONSULTATION_PREFERENCE_RESET', [
            'preference_id' => $preference->id,
            'user_id' => $preference->user_id,
        ], $preference, 'Doctor consultation workspace preference reset.');

        $preference->delete();

        return back()->with('success', __('consultation_specialties.admin.deleted'));
    }
}

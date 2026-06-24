<?php

namespace App\Http\Controllers\Admin\Patients;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientMergeLog;
use App\Models\PatientMergeRequest;
use App\Services\PatientMergePreviewService;
use App\Services\PatientMergeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatientMergeController extends Controller
{
    public function __construct(
        private PatientMergePreviewService $previewService,
        private PatientMergeService $mergeService,
    ) {}

    public function index()
    {
        $recentRequests = PatientMergeRequest::with(['mainPatient', 'duplicatePatient', 'requestedBy', 'executedBy'])
            ->latest()
            ->limit(15)
            ->get();

        return view('patients.merge.index', compact('recentRequests'));
    }

    /**
     * AJAX: search patients to assign as the main or duplicate folder.
     */
    public function search(Request $request)
    {
        $term = (string) $request->get('q', '');
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::with('aliases')
            ->search($term)
            ->limit(10)
            ->get()
            ->map(function (Patient $patient) {
                return [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'is_deceased' => (bool) $patient->is_deceased,
                    'is_merged' => $patient->isMerged(),
                ];
            });

        return response()->json($patients);
    }

    public function compare(Request $request)
    {
        $data = $request->validate([
            'main_patient_number' => ['required', 'string', 'max:191', 'different:duplicate_patient_number'],
            'duplicate_patient_number' => ['required', 'string', 'max:191', 'different:main_patient_number'],
        ]);

        $mainPatient = $this->resolvePatientByNumber($data['main_patient_number'], 'main_patient_number')
            ->load(['insurances.insuranceProvider', 'emergencyContacts', 'aliases'])
            ->getFinalPatient();
        $duplicatePatient = $this->resolvePatientByNumber($data['duplicate_patient_number'], 'duplicate_patient_number')
            ->load(['insurances.insuranceProvider', 'emergencyContacts', 'aliases', 'mergedToPatient']);
        $preview = $this->previewService->preview($mainPatient, $duplicatePatient);
        $fields = $this->previewService->demographicFields();

        return view('patients.merge.compare', compact('mainPatient', 'duplicatePatient', 'preview', 'fields'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'main_patient_number' => ['required', 'string', 'max:191', 'different:duplicate_patient_number'],
            'duplicate_patient_number' => ['required', 'string', 'max:191', 'different:main_patient_number'],
            // High-risk: merging two patient folders is irreversible — a reason is mandatory.
            'reason' => ['required', 'string', 'max:2000'],
            'field_resolution' => ['nullable', 'array'],
            'confirmed' => ['accepted'],
            'execute_now' => ['nullable', 'boolean'],
        ], [
            'reason.required' => 'Please document why these folders are the same patient before merging.',
            'confirmed.accepted' => 'Please confirm you have reviewed the folders before merging.',
        ]);

        try {
            $mainPatient = $this->resolvePatientByNumber($data['main_patient_number'], 'main_patient_number');
            $duplicatePatient = $this->resolvePatientByNumber($data['duplicate_patient_number'], 'duplicate_patient_number');
            $approve = $request->user()->can('patients.merge.execute');

            $mergeRequest = $this->mergeService->createRequest(
                mainPatient: $mainPatient,
                duplicatePatient: $duplicatePatient,
                user: $request->user(),
                fieldResolution: $data['field_resolution'] ?? [],
                reason: $data['reason'] ?? null,
                approve: $approve,
            );

            if ($request->boolean('execute_now') && $approve) {
                $mergeRequest = $this->mergeService->execute($mergeRequest, $request->user());

                return redirect()
                    ->route('admin.patients.merge.requests.show', $mergeRequest)
                    ->with('success', __('messages.patient_merge.merged'));
            }

            return redirect()
                ->route('admin.patients.merge.requests.show', $mergeRequest)
                ->with('success', __('messages.patient_merge.request_created'));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['duplicate_patient_number' => $e->getMessage()]);
        }
    }

    public function show(PatientMergeRequest $mergeRequest)
    {
        $mergeRequest->load([
            'mainPatient.aliases',
            'duplicatePatient.mergedToPatient',
            'requestedBy',
            'approvedBy',
            'executedBy',
            'logs.performedBy',
        ]);

        return view('patients.merge.show', compact('mergeRequest'));
    }

    public function execute(Request $request, PatientMergeRequest $mergeRequest)
    {
        try {
            $mergeRequest = $this->mergeService->execute($mergeRequest, $request->user());

            return redirect()
                ->route('admin.patients.merge.requests.show', $mergeRequest)
                ->with('success', __('messages.patient_merge.merged'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['merge_request' => $e->getMessage()]);
        }
    }

    public function logs()
    {
        $logs = PatientMergeLog::with(['mergeRequest', 'mainPatient', 'duplicatePatient', 'performedBy'])
            ->latest('occurred_at')
            ->paginate(50);

        return view('patients.merge.logs', compact('logs'));
    }

    private function findPatientByNumber(?string $patientNumber): ?Patient
    {
        if (blank($patientNumber)) {
            return null;
        }

        return Patient::with(['aliases', 'mergedToPatient'])
            ->where('patient_number', trim($patientNumber))
            ->first();
    }

    private function resolvePatientByNumber(?string $patientNumber, string $field): Patient
    {
        $patient = $this->findPatientByNumber($patientNumber);

        if (! $patient) {
            throw ValidationException::withMessages([
                $field => 'No patient folder was found for that patient number.',
            ]);
        }

        return $patient;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientMergeLog;
use App\Models\PatientMergeRequest;
use App\Services\PatientMergePreviewService;
use App\Services\PatientMergeService;
use Illuminate\Http\Request;

class PatientMergeController extends Controller
{
    public function __construct(
        private PatientMergePreviewService $previewService,
        private PatientMergeService $mergeService,
    ) {}

    public function index(Request $request)
    {
        $patients = collect();

        if ($request->filled('search')) {
            $patients = Patient::with(['aliases', 'mergedToPatient'])
                ->search($request->query('search'))
                ->latest()
                ->limit(30)
                ->get();
        }

        $recentRequests = PatientMergeRequest::with(['mainPatient', 'duplicatePatient', 'requestedBy', 'executedBy'])
            ->latest()
            ->limit(15)
            ->get();

        return view('patients.merge.index', compact('patients', 'recentRequests'));
    }

    public function compare(Request $request)
    {
        $data = $request->validate([
            'main_patient_id' => ['required', 'exists:patients,id'],
            'duplicate_patient_id' => ['required', 'exists:patients,id', 'different:main_patient_id'],
        ]);

        $mainPatient = Patient::with(['insurances.insuranceProvider', 'emergencyContacts', 'aliases'])->findOrFail($data['main_patient_id'])->getFinalPatient();
        $duplicatePatient = Patient::with(['insurances.insuranceProvider', 'emergencyContacts', 'aliases', 'mergedToPatient'])->findOrFail($data['duplicate_patient_id']);
        $preview = $this->previewService->preview($mainPatient, $duplicatePatient);
        $fields = $this->previewService->demographicFields();

        return view('patients.merge.compare', compact('mainPatient', 'duplicatePatient', 'preview', 'fields'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'main_patient_id' => ['required', 'exists:patients,id'],
            'duplicate_patient_id' => ['required', 'exists:patients,id', 'different:main_patient_id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'field_resolution' => ['nullable', 'array'],
            'confirmed' => ['accepted'],
            'execute_now' => ['nullable', 'boolean'],
        ]);

        try {
            $mainPatient = Patient::findOrFail($data['main_patient_id']);
            $duplicatePatient = Patient::findOrFail($data['duplicate_patient_id']);
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
                    ->with('success', 'Patient folders merged successfully.');
            }

            return redirect()
                ->route('admin.patients.merge.requests.show', $mergeRequest)
                ->with('success', 'Patient merge request created.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['duplicate_patient_id' => $e->getMessage()]);
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
                ->with('success', 'Patient folders merged successfully.');
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
}

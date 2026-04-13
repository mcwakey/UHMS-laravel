<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService,
    ) {}

    public function index(Request $request)
    {
        $visits = $this->visitService->list($request->all());
        $stats = $this->visitService->todayStats();
        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('visits.index', compact('visits', 'stats', 'departments', 'doctors'));
    }

    public function create(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();
        $selectedPatient = null;

        if ($request->has('patient_id')) {
            $selectedPatient = Patient::find($request->patient_id);
        }

        return view('visits.create', compact('departments', 'doctors', 'selectedPatient'));
    }

    public function store(StoreVisitRequest $request)
    {
        $data = $request->validated();

        // Use createWithServices when services are included; fall back to plain create
        if (!empty($data['services'])) {
            $visit = $this->visitService->createWithServices($data);
        } else {
            $visit = $this->visitService->create($data);
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} created and patient added to queue.");
    }

    public function show(Visit $visit)
    {
        $visit->load([
            'patient',
            'department',
            'assignedDoctor',
            'createdBy',
            'statusLogs.changedBy',
            'queueEntries.department',
        ]);

        return view('visits.show', compact('visit'));
    }

    public function transition(Request $request, Visit $visit)
    {
        $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = VisitStatus::from($request->status);

        if (!$visit->canTransitionTo($newStatus)) {
            return back()->with('error', "Cannot transition from {$visit->status->label()} to {$newStatus->label()}.");
        }

        $this->visitService->transition($visit, $newStatus, $request->notes);

        return back()->with('success', "Visit status updated to {$newStatus->label()}.");
    }

    public function patientSearch(Request $request)
    {
        $term = $request->get('q', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::search($term)
            ->active()
            ->select('id', 'patient_number', 'first_name', 'last_name', 'other_names', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'text' => "{$p->patient_number} — {$p->full_name}",
                'patient_number' => $p->patient_number,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
            ]);

        return response()->json($patients);
    }
}

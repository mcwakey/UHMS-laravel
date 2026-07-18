<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use App\Enums\BedStatus;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\InpatientWorkspaceScope;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdmissionRequestController extends Controller
{
    public function __construct(
        private AdmissionRequestService $requests,
        private InpatientWorkspaceScope $inpatientScope,
        private WorkspaceRouteResolver $workspaceRoutes,
    ) {}

    public function index(Request $request)
    {
        $filters = array_merge(array_filter([
            'status' => $request->route('status'),
        ]), $request->only(['search', 'status', 'source_type']));
        $admissionRequests = $this->requests->list($filters);

        $legacyVisitQuery = Visit::with(['patient', 'department', 'activeConsultationRoute.doctor'])
            ->where('status', VisitStatus::ADMITTING->value)
            ->whereDoesntHave('admissionRequests', fn ($query) => $query->open())
            ->when($request->filled('search'), fn ($query) => $query->search($request->search));

        $legacyVisitCount = (clone $legacyVisitQuery)->count();
        $legacyVisits = $legacyVisitQuery->latest('updated_at')
            ->limit(15)
            ->get();

        return view('admissions.requests', [
            'admissionRequests' => $admissionRequests,
            'legacyVisits' => $legacyVisits,
            'searchQuery' => $request->input('search', ''),
            'statuses' => AdmissionRequestStatus::cases(),
            'sources' => AdmissionRequestSource::cases(),
            'selectedStatus' => $request->input('status', ''),
            'selectedSource' => $request->input('source_type', ''),
            'totalPending' => $admissionRequests->total() + $legacyVisitCount,
        ]);
    }

    public function create()
    {
        return view('admissions.request-create', [
            'admittingVisits' => Visit::with('patient')
                ->where('status', VisitStatus::ADMITTING->value)
                ->latest('updated_at')
                ->get(),
            'wards' => tap(Ward::active(), fn ($query) => $this->inpatientScope->wards($query))->orderBy('name')->get(),
            'sources' => AdmissionRequestSource::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['nullable', 'exists:visits,id'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'source_type' => ['required', new Enum(AdmissionRequestSource::class)],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'requested_ward_id' => ['nullable', 'exists:wards,id'],
            'preferred_bed_type' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'max:50'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:2000'],
            'clinical_summary' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! empty($data['visit_id'])) {
            $visit = Visit::findOrFail($data['visit_id']);
            $admissionRequest = $this->requests->createForVisit(
                $visit,
                $data['source_type'],
                $data['source_id'] ?? null,
                $data,
                $request->user()
            );
        } else {
            $request->validate(['patient_id' => ['required', 'exists:patients,id']]);
            $admissionRequest = $this->requests->create($data, $request->user());
        }

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.requests.show'), $admissionRequest)
            ->with('success', __('admissions.request_messages.created'));
    }

    public function show(AdmissionRequest $admissionRequest)
    {
        $admissionRequest->load([
            'patient',
            'visit.department',
            'requestedBy',
            'acceptedBy',
            'rejectedBy',
            'cancelledBy',
            'requestedWard',
            'reservedBed.ward',
            'activeBedReservation.reservedBy',
            'admission',
        ]);

        $availableBeds = Bed::with('ward')
            ->where('status', BedStatus::AVAILABLE)
            ->when($admissionRequest->requested_ward_id, fn ($query) => $query->where('ward_id', $admissionRequest->requested_ward_id))
            ->when($this->inpatientScope->departmentId(), fn ($query, $departmentId) => $query
                ->whereHas('ward', fn ($ward) => $ward->where('department_id', $departmentId)))
            ->orderBy('ward_id')
            ->orderBy('bed_number')
            ->get();

        return view('admissions.request-show', [
            'admissionRequest' => $admissionRequest,
            'availableBeds' => $availableBeds,
        ]);
    }

    public function accept(Request $request, AdmissionRequest $admissionRequest)
    {
        $this->requests->accept($admissionRequest, $request->user());

        return back()->with('success', __('admissions.request_messages.accepted'));
    }

    public function reject(Request $request, AdmissionRequest $admissionRequest)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->requests->reject($admissionRequest, $data['reason'], $request->user());

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.requests'))
            ->with('success', __('admissions.request_messages.rejected'));
    }

    public function cancel(Request $request, AdmissionRequest $admissionRequest)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->requests->cancel($admissionRequest, $data['reason'], $request->user());

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.requests'))
            ->with('success', __('admissions.request_messages.cancelled'));
    }

    public function bedPending(Request $request, AdmissionRequest $admissionRequest)
    {
        $this->requests->markBedPending($admissionRequest, $request->user());

        return back()->with('success', __('admissions.request_messages.bed_pending'));
    }

    public function reserveBed(Request $request, AdmissionRequest $admissionRequest)
    {
        $data = $request->validate(['bed_id' => ['required', 'exists:beds,id']]);
        $this->requests->reserveBed($admissionRequest, Bed::findOrFail($data['bed_id']), $request->user());

        return back()->with('success', __('admissions.request_messages.bed_reserved'));
    }

    public function convert(Request $request, AdmissionRequest $admissionRequest)
    {
        if ($admissionRequest->converted_at || $admissionRequest->admission) {
            return redirect()
                ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admissionRequest->admission)
                ->with('success', __('admissions.request_messages.already_converted'));
        }

        return redirect()->route($this->workspaceRoutes->routeName('admin.admissions.create'), [
            'admission_request_id' => $admissionRequest->id,
            'visit_id' => $admissionRequest->visit_id,
            'bed_id' => $admissionRequest->reserved_bed_id,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmergencyArrivalMode;
use App\Enums\EmergencyCaseStatus;
use App\Enums\EmergencyDisposition;
use App\Enums\EmergencyTriageCategory;
use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Services\EmergencyQueueService;
use App\Services\EmergencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmergencyCaseController extends Controller
{
    public function __construct(
        protected EmergencyService $emergencyService,
        protected EmergencyQueueService $queueService,
    ) {}

    /* ------------------------------------------------------------------
     | Dashboard
     |------------------------------------------------------------------ */
    public function dashboard()
    {
        abort_unless(Gate::allows('emergency.dashboard.view'), 403);

        $stats        = $this->queueService->getDashboardStats();
        $triageCounts = $this->queueService->getTriageCounts();
        $queue        = $this->queueService->getOpenQueue()->take(15);

        return view('emergency.dashboard', compact('stats', 'triageCounts', 'queue'));
    }

    /* ------------------------------------------------------------------
     | Queue (full)
     |------------------------------------------------------------------ */
    public function queue()
    {
        abort_unless(Gate::allows('emergency.queue.view'), 403);

        $queue          = $this->queueService->getOpenQueue();
        $awaitingTriage = $this->queueService->getAwaitingTriage();

        return view('emergency.queue', compact('queue', 'awaitingTriage'));
    }

    /* ------------------------------------------------------------------
     | Cases listing (all, with filters)
     |------------------------------------------------------------------ */
    public function index(Request $request)
    {
        abort_unless(Gate::allows('emergency.case.view'), 403);

        $q = EmergencyCase::with(['patient', 'treatmentArea', 'assignedDoctor'])
            ->latest('arrival_time');

        if ($s = $request->string('status')->toString()) {
            $q->where('status', $s);
        }
        if ($t = $request->string('triage')->toString()) {
            $q->where('triage_category', $t);
        }
        if ($d = $request->string('disposition')->toString()) {
            $q->where('disposition', $d);
        }
        if ($search = $request->string('search')->toString()) {
            $q->where(function ($qq) use ($search) {
                $qq->where('emergency_number', 'like', "%{$search}%")
                   ->orWhereHas('patient', fn ($pp) => $pp
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"));
            });
        }

        $cases = $q->paginate(20)->withQueryString();

        return view('emergency.cases.index', [
            'cases'         => $cases,
            'statuses'      => EmergencyCaseStatus::cases(),
            'triages'       => EmergencyTriageCategory::cases(),
            'dispositions'  => EmergencyDisposition::cases(),
        ]);
    }

    /* ------------------------------------------------------------------
     | New case form
     |------------------------------------------------------------------ */
    public function create(Request $request)
    {
        abort_unless(Gate::allows('emergency.case.create'), 403);

        $patient = $request->filled('patient_id')
            ? Patient::find($request->integer('patient_id'))
            : null;

        return view('emergency.cases.create', [
            'patient'      => $patient,
            'arrivalModes' => EmergencyArrivalMode::cases(),
            'triages'      => EmergencyTriageCategory::cases(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Gate::allows('emergency.case.create'), 403);

        $data = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'arrival_mode'      => 'nullable|string',
            'arrival_time'      => 'nullable|date',
            'brought_by'        => 'nullable|string|max:255',
            'accompanied_by'    => 'nullable|string|max:255',
            'referral_source'   => 'nullable|string|max:255',
            'chief_complaint'   => 'nullable|string',
            'triage_category'   => 'nullable|string',
        ]);

        $case = $this->emergencyService->registerCase($data);

        return redirect()
            ->route('admin.emergency.cases.show', $case)
            ->with('success', "Emergency case {$case->emergency_number} registered.");
    }

    /* ------------------------------------------------------------------
     | Case workspace
     |------------------------------------------------------------------ */
    public function show(EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.case.view'), 403);

        $emergencyCase->load([
            'patient',
            'visit.invoices.items',
            'visit.vitals',
            'treatmentArea',
            'assignedDoctor',
            'assignedNurse',
            'registeredBy',
            'dispositionBy',
            'admission.bed.ward',
        ]);

        $doctors      = User::role(['Emergency Doctor'])->orderBy('first_name')->orderBy('last_name')->get();
        $nurses       = User::role(['Emergency Nurse'])->orderBy('first_name')->orderBy('last_name')->get();
        $beds         = Bed::available()->with('ward')->orderBy('bed_number')->get();
        $services     = ServiceCatalog::where('is_active', true)->orderBy('name')->limit(200)->get();
        $products     = Product::where('is_active', true)->orderBy('name')->limit(200)->get();

        return view('emergency.cases.show', [
            'case'         => $emergencyCase,
            'arrivalModes' => EmergencyArrivalMode::cases(),
            'triages'      => EmergencyTriageCategory::cases(),
            'dispositions' => EmergencyDisposition::cases(),
            'doctors'      => $doctors,
            'nurses'       => $nurses,
            'beds'         => $beds,
            'services'     => $services,
            'products'     => $products,
        ]);
    }

    /* ------------------------------------------------------------------
     | Triage
     |------------------------------------------------------------------ */
    public function triage(Request $request, EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.triage.create'), 403);

        $data = $request->validate([
            'triage_category' => 'required|string',
        ]);

        $this->emergencyService->triage($emergencyCase, $data);

        return back()->with('success', 'Triage recorded.');
    }

    /* ------------------------------------------------------------------
     | Assignment
     |------------------------------------------------------------------ */
    public function assign(Request $request, EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.case.update'), 403);

        $data = $request->validate([
            'assigned_doctor_id' => 'nullable|exists:users,id',
            'assigned_nurse_id'  => 'nullable|exists:users,id',
            'treatment_area_id'  => 'nullable|exists:departments,id',
        ]);

        $this->emergencyService->assign($emergencyCase, $data);

        return back()->with('success', 'Case assigned.');
    }

    /* ------------------------------------------------------------------
     | Bill a service / procedure to the visit invoice
     |------------------------------------------------------------------ */
    public function billService(Request $request, EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.orders.create'), 403);

        $data = $request->validate([
            'service_id'  => 'required|exists:service_catalog,id',
            'quantity'    => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $service = ServiceCatalog::findOrFail($data['service_id']);

        // Use composite source id so multiple charges of the same service are allowed.
        $sourceId = ($emergencyCase->id * 1_000_000) + $service->id + random_int(1, 999);

        $this->emergencyService->billService(
            case:        $emergencyCase,
            service:     $service,
            sourceType:  'emergency_service',
            sourceId:    $sourceId,
            quantity:    $data['quantity'] ?? 1,
            description: $data['description'] ?? null,
        );

        return back()->with('success', "Service '{$service->name}' billed to case invoice.");
    }

    /* ------------------------------------------------------------------
     | Consume a product (drug / consumable)
     |------------------------------------------------------------------ */
    public function consumeProduct(Request $request, EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.consumables.consume'), 403);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($data['product_id']);

        $this->emergencyService->consumeProduct(
            case:     $emergencyCase,
            product:  $product,
            quantity: (float) $data['quantity'],
            notes:    $data['notes'] ?? null,
        );

        return back()->with('success', "Consumed {$data['quantity']} × {$product->name}.");
    }

    /* ------------------------------------------------------------------
     | Disposition
     |------------------------------------------------------------------ */
    public function disposition(Request $request, EmergencyCase $emergencyCase)
    {
        abort_unless(Gate::allows('emergency.disposition.set'), 403);

        $data = $request->validate([
            'disposition'                => 'required|string',
            'notes'                      => 'nullable|string',
            'discharge_summary'          => 'nullable|string',
            'referral_facility'          => 'nullable|string|max:255',
            'referral_reason'            => 'nullable|string',
            'death_time'                 => 'nullable|date',
            'death_cause'                => 'nullable|string|max:255',
            'bed_id'                     => 'nullable|exists:beds,id',
            'admitting_diagnosis'        => 'nullable|string',
            'admission_type'             => 'nullable|string',
            'admission_fee_service_id'   => 'nullable|exists:service_catalog,id',
            'consumable_fee_service_id'  => 'nullable|exists:service_catalog,id',
        ]);

        try {
            $this->emergencyService->setDisposition($emergencyCase, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.emergency.cases.show', $emergencyCase)
            ->with('success', 'Disposition saved.');
    }
}

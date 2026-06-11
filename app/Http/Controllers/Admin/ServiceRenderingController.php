<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\ServiceRendering;
use App\Models\Visit;
use App\Services\BillingService;
use App\Services\ServiceRenderingQueryService;
use App\Services\ServiceRenderingReportService;
use App\Services\ServiceRenderingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceRenderingController extends Controller
{
    public function __construct(
        private ServiceRenderingQueryService $queries,
        private ServiceRenderingReportService $reports,
        private ServiceRenderingService $service,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request, [
            'search',
            'status',
            'department_id',
            'date_range',
            'date_from',
            'date_to',
        ]);

        // Departments that actually have rendering-tracked services (for the "Add Service" modal).
        $renderableDepartmentIds = ServiceCatalog::query()
            ->where('is_active', true)
            ->where('requires_rendering_tracking', true)
            ->whereNotNull('department_id')
            ->distinct()
            ->pluck('department_id');

        return view('service-renderings.index', [
            'renderings' => $this->queries->paginate($filters, $request->user()),
            'summary' => $this->reports->summary($filters, $request->user()),
            'filters' => $filters,
            'statuses' => ServiceRendering::statuses(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'renderableDepartments' => Department::query()->whereIn('id', $renderableDepartmentIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Bill an extra service to a visit's invoice. Billing a rendering-tracked
     * service auto-creates its ServiceRendering task (see BillingService).
     */
    public function store(Request $request, BillingService $billing)
    {
        $data = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'service_id' => ['required', 'exists:service_catalog,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $visit = Visit::findOrFail($data['visit_id']);
        $service = ServiceCatalog::findOrFail($data['service_id']);

        try {
            $billing->addItemToVisitInvoice(
                $visit,
                $service,
                'service_catalog',
                null,
                (int) ($data['quantity'] ?? 1),
                $service->department_id,
                $data['notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.service-renderings.index')
            ->with('success', __('messages.service_rendering.billed', ['service' => $service->name, 'visit' => $visit->visit_number]));
    }

    /** select2 JSON: open visits to bill against (search visit # or patient). */
    public function visitSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $visits = Visit::query()
            ->with('patient:id,patient_number,first_name,last_name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('visit_number', 'like', "%{$q}%")
                        ->orWhereHas('patient', function ($p) use ($q) {
                            $p->where('first_name', 'like', "%{$q}%")
                                ->orWhere('last_name', 'like', "%{$q}%")
                                ->orWhere('patient_number', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('visit_date')
            ->limit(20)
            ->get();

        return response()->json($visits->map(fn ($v) => [
            'id' => $v->id,
            'text' => $v->visit_number.' — '.($v->patient?->full_name ?? 'Patient'),
            'patient_name' => $v->patient?->full_name,
            'patient_number' => $v->patient?->patient_number,
            'visit_date' => $v->visit_date?->format('d M Y'),
        ]));
    }

    /** select2 JSON: active, rendering-tracked services (these create a rendering). */
    public function serviceSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $services = ServiceCatalog::query()
            ->with('department:id,name')
            ->where('is_active', true)
            ->where('requires_rendering_tracking', true)
            ->when($request->query('department_id'), fn ($x, $deptId) => $x->where('department_id', $deptId))
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($services->map(fn ($s) => [
            'id' => $s->id,
            'text' => $s->name,
            'price' => (float) ($s->price ?? 0),
            'department' => $s->department?->name,
        ]));
    }

    public function show(Request $request, ServiceRendering $serviceRendering)
    {
        $serviceRendering->load($this->service->defaultRelations());
        $this->service->assertCan($request->user(), $serviceRendering, 'service_rendering.view');

        return view('service-renderings.show', [
            'rendering' => $serviceRendering,
        ]);
    }

    private function filters(Request $request, array $keys): array
    {
        $filters = $request->only($keys);
        $dateRange = trim((string) ($filters['date_range'] ?? ''));

        if ($dateRange !== '') {
            $parts = preg_split('/\s+(?:to|-)\s+/', $dateRange);
            $filters['date_from'] = $this->normalizeDate($parts[0] ?? null);
            $filters['date_to'] = $this->normalizeDate($parts[1] ?? ($parts[0] ?? null));
        } else {
            $filters['date_from'] = $this->normalizeDate($filters['date_from'] ?? null);
            $filters['date_to'] = $this->normalizeDate($filters['date_to'] ?? null);
        }

        if (! empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_to'] = $filters['date_from'];
        }

        if (! empty($filters['date_to']) && empty($filters['date_from'])) {
            $filters['date_from'] = $filters['date_to'];
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            if (Carbon::parse($filters['date_from'])->gt(Carbon::parse($filters['date_to']))) {
                [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
            }

            $filters['date_range'] = $filters['date_from'].' to '.$filters['date_to'];
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}

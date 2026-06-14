<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Enums\ResultType;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Services\ConsumableUsageService;
use App\Services\LabService;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function __construct(
        protected LabService $labService,
        protected ConsumableUsageService $consumableUsage,
    ) {}

    /**
     * Billed/accepted investigation requests ready for result entry.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'urgency', 'search', 'date_from', 'date_to', 'department_id']);

        if (!empty($filters['department_id'])) {
            $filters['target_department_id'] = $filters['department_id'];
        }

        $requests = $this->labService->getResultRequests([
            'status' => $filters['status'] ?? null,
            'urgency' => $filters['urgency'] ?? null,
            'search' => $filters['search'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'target_department_id' => $filters['target_department_id'] ?? null,
        ]);
        $departments = $this->labService->getInvestigationDepartments();

        return view('lab.results', compact('requests', 'departments'));
    }

    /**
     * Open a billed/accepted investigation request for result entry.
     */
    public function showRequest(LabRequest $labRequest)
    {
        $request = $this->labService->getRequestDetails($labRequest);
        $hasAcceptedOrBilledItems = $request->items->contains(
            fn ($item) => $item->isAccepted() || $item->billed_at || $item->invoice_item_id
        );

        if (! $hasAcceptedOrBilledItems) {
            return redirect()
                ->route('admin.lab.requests.show', $request)
                ->with('error', __('messages.lab.accept_items_first'));
        }

        $backRoute = route('admin.lab.results.index');
        $showBillingAcceptance = false;

        return view('lab.process', compact('request', 'backRoute', 'showBillingAcceptance'));
    }

    /**
     * Enter result for a single investigation item (supports all result types).
     */
    public function store(Request $request, LabRequestItem $item)
    {
        $resultType = ResultType::tryFrom($request->input('result_type', 'parameters'))
            ?? ResultType::PARAMETERS;

        // Configurable overall result type (per investigation/test). Restrict to the
        // typed variants; anything else (incl. legacy/unset) is treated as free text.
        $overallType = $request->input('overall_result_type');
        $typedOverall = in_array($overallType, [
            \App\Models\ServiceCatalog::OVERALL_RESULT_NUMERIC,
            \App\Models\ServiceCatalog::OVERALL_RESULT_BOOLEAN,
            \App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE,
        ], true);

        $rules = [
            'result_type' => ['required', 'string'],
            'is_abnormal' => ['nullable', 'boolean'],
            'remarks'     => ['nullable', 'string', 'max:2000'],
            // Attachment is always allowed regardless of result type so users
            // can pair an image / document with parameters or a written report.
            'result_file' => ['nullable', 'file', 'max:20480'], // 20MB
            // Investigation Catalogue criteria-based values (optional)
            'values'                       => ['nullable', 'array'],
            'values.*.value'               => ['nullable', 'string', 'max:500'],
            'values.*.name'                => ['nullable', 'string', 'max:191'],
            'values.*.unit'                => ['nullable', 'string', 'max:50'],
            'values.*.reference_range'     => ['nullable', 'string', 'max:191'],
            'values.*.flag'                => ['nullable', 'string', 'max:30'],
            // Consumables actually used to perform this test (optional)
            'consumables'              => ['nullable', 'array'],
            'consumables.*.product_id' => ['required_with:consumables.*.quantity', 'integer', 'exists:products,id'],
            'consumables.*.quantity'   => ['required_with:consumables.*.product_id', 'numeric', 'gt:0'],
            'consumables.*.notes'      => ['nullable', 'string', 'max:255'],
        ];

        if ($resultType === ResultType::RICHTEXT) {
            $rules['result_text'] = ['required', 'string'];
        } elseif ($resultType->isFileBased()) {
            $rules['result_file'] = ['required', 'file', 'max:20480']; // 20MB
        } elseif ($typedOverall) {
            // Validate the configured overall result type. Canonical values only.
            $rules['overall_result_type'] = ['required', 'in:numeric,boolean,positive_negative'];
            $rules['overall_result_value'] = match ($overallType) {
                \App\Models\ServiceCatalog::OVERALL_RESULT_NUMERIC           => ['required', 'numeric'],
                \App\Models\ServiceCatalog::OVERALL_RESULT_BOOLEAN          => ['required', 'in:true,false,1,0'],
                \App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE => ['required', 'in:positive,negative'],
                default                                                      => ['nullable'],
            };
            // result_value is derived from the canonical value below.
            $rules['result_value'] = ['nullable', 'string', 'max:5000'];
        } else {
            // For criteria-based result entry, result_value can be derived/empty.
            if ($request->filled('values')) {
                $rules['result_value'] = ['nullable', 'string', 'max:5000'];
            } else {
                $rules['result_value'] = ['required', 'string', 'max:5000'];
            }
        }

        $validated = $request->validate($rules);

        // Map the entered overall result into canonical, reportable columns.
        if ($typedOverall) {
            $validated = $this->mapOverallResult($validated, $overallType, $item);
        } elseif ($resultType === ResultType::PARAMETERS) {
            // free_text overall result: mirror into the typed text column for reporting.
            $validated['overall_result_type'] = \App\Models\ServiceCatalog::OVERALL_RESULT_FREE_TEXT;
        }
        // Pass the uploaded file separately because $request->validate strips
        // unrecognised non-validated keys.
        if ($request->hasFile('result_file')) {
            $validated['result_file'] = $request->file('result_file');
        }

        // If values[] is provided but no result_value, build a summary string.
        if (empty($validated['result_value'] ?? null) && !empty($validated['values'] ?? [])) {
            $summary = collect($validated['values'])
                ->map(function ($v, $key) {
                    $name = $v['name'] ?? $key;
                    $val = $v['value'] ?? '';
                    return $val !== '' ? "{$name}: {$val}" : null;
                })
                ->filter()
                ->implode(' | ');
            $validated['result_value'] = $summary ?: '(criteria-based result)';
        }

        // For a free-text overall result, mirror the final text into the canonical
        // overall_result_text column so reporting can read it uniformly.
        if (($validated['overall_result_type'] ?? null) === \App\Models\ServiceCatalog::OVERALL_RESULT_FREE_TEXT) {
            $validated['overall_result_text'] = $validated['result_value'] ?? null;
        }

        try {
            $result = $this->labService->enterResult($item, $validated);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Persist criteria-based values if provided.
        if (!empty($validated['values'] ?? []) && $result instanceof LabResult) {
            $sort = 0;
            foreach ($validated['values'] as $criteriaId => $row) {
                $value = $row['value'] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $result->values()->create([
                    'criteria_id'     => is_numeric($criteriaId) ? (int) $criteriaId : null,
                    'name'            => $row['name'] ?? '',
                    'value'           => (string) $value,
                    'unit'            => $row['unit'] ?? null,
                    'reference_range' => $row['reference_range'] ?? null,
                    'flag'            => $row['flag'] ?? null,
                    'sort_order'      => $sort++,
                ]);
            }
        }

        // Record actual consumable usage (deducts stock from the lab/investigation location).
        if ($result instanceof LabResult && $request->filled('consumables')) {
            try {
                $visit   = $item->labRequest?->visit;
                $service = $item->service ?? ($item->service_id ? \App\Models\ServiceCatalog::find($item->service_id) : null);
                if ($visit) {
                    $this->consumableUsage->recordUsageForSource(
                        $visit,
                        $service,
                        'investigation_result',
                        $result->id,
                        $request->input('consumables', []),
                        \Illuminate\Support\Facades\Auth::id(),
                    );
                }
            } catch (\Throwable $e) {
                return back()->with('warning', 'Result saved, but consumable usage failed: '.$e->getMessage());
            }
        }

        return back()->with('success', __('messages.lab.result_saved'));
    }

    /**
     * Translate a submitted overall result into canonical, reportable columns
     * and a backward-compatible result_value text. Canonical DB values only
     * (numeric / true|false / positive|negative) — never translated labels.
     */
    private function mapOverallResult(array $validated, string $overallType, LabRequestItem $item): array
    {
        $value = $validated['overall_result_value'] ?? null;
        $validated['overall_result_type'] = $overallType;
        unset($validated['overall_result_value']);

        switch ($overallType) {
            case \App\Models\ServiceCatalog::OVERALL_RESULT_NUMERIC:
                $numeric = (float) $value;
                $unit = $item->service?->overall_result_unit;
                $validated['overall_result_numeric'] = $numeric;
                $validated['overall_result_unit'] = $unit;
                $text = rtrim(rtrim(number_format($numeric, 4, '.', ''), '0'), '.');
                $validated['result_value'] = $unit ? "{$text} {$unit}" : $text;
                break;

            case \App\Models\ServiceCatalog::OVERALL_RESULT_BOOLEAN:
                $bool = in_array((string) $value, ['true', '1'], true);
                $validated['overall_result_boolean'] = $bool;
                $validated['result_value'] = $bool ? 'true' : 'false';
                break;

            case \App\Models\ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE:
                $outcome = $value === 'positive' ? 'positive' : 'negative';
                $validated['overall_result_outcome'] = $outcome;
                $validated['result_value'] = $outcome;
                break;
        }

        return $validated;
    }

    /**
     * Batch enter results (parameters type only).
     */
    public function batchStore(Request $request, LabRequest $labRequest)
    {
        $validated = $request->validate([
            'results'                   => ['required', 'array'],
            'results.*.result_value'    => ['nullable', 'string', 'max:5000'],
            'results.*.is_abnormal'     => ['nullable', 'boolean'],
            'results.*.remarks'         => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->labService->batchEnterResults($labRequest, $validated['results']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.lab.results_saved'));
    }

    /**
     * Verify a lab result.
     *
     * Authorization is enforced both at the route level (can:lab.results.verify)
     * and here in the controller for defense-in-depth.
     */
    public function verify(\Illuminate\Http\Request $request, LabResult $result)
    {
        if (! $request->user()?->can('lab.results.verify')) {
            abort(403, 'You are not authorized to verify lab results.');
        }

        try {
            $this->labService->verifyResult($result);
            return back()->with('success', __('messages.lab.result_verified'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX: render result snippet for modal viewing (used by both lab page and consultation).
     */
    public function view(LabRequestItem $item)
    {
        $item->load([
            'labRequest.patient',
            'service.investigationHeaders.criteria',
            'service.investigationCriteria',
            'labTest.criteria',
            'result.values',
            'result.performedBy',
            'result.verifiedBy',
        ]);

        if (!$item->result) {
            return response()->view('lab._result_modal', ['item' => $item, 'noResult' => true]);
        }

        return view('lab._result_modal', ['item' => $item, 'noResult' => false]);
    }

    /**
     * Print a verified result.
     */
    public function print(LabRequestItem $item)
    {
        $item->load([
            'labRequest.patient',
            'labRequest.requestedBy',
            'labRequest.targetDepartment',
            'labRequest.visit.activeConsultationRoute.doctor',
            'labRequest.visit.pendingConsultationRoutes.doctor',
            'service.investigationHeaders.criteria',
            'service.investigationCriteria',
            'labTest.criteria',
            'result.values',
            'result.performedBy',
            'result.verifiedBy',
        ]);

        if (!$item->result || !$item->result->is_verified) {
            abort(403, 'Only verified results can be printed.');
        }

        // Official print of a verified result (not a page view) — worth auditing.
        app(\App\Services\ActivityLogService::class)->log(
            \App\Enums\LogModule::INVESTIGATION,
            'RESULT_PRINTED',
            $item->labRequest->toActivityContext() + array_filter([
                'investigation_result_id' => $item->result?->id,
                'service_id' => $item->service_id,
            ], fn ($v) => $v !== null),
            $item->result,
            'Result printed: ' . ($item->name ?? $item->service?->name ?? $item->labTest?->name ?? $item->labRequest->request_number),
        );

        return view('lab.print', ['item' => $item]);
    }
}

<?php

namespace App\Services;

use App\Enums\ResultType;
use App\Events\LabRequestCreated;
use App\Events\LabResultsCompleted;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\Visit;
use App\Services\Billing\PaymentGateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class LabService
{
    public function __construct(private PaymentGateService $paymentGate) {}

    /*
    |--------------------------------------------------------------------------
    | Lab Test Catalog Management
    |--------------------------------------------------------------------------
    */

    public function getCategories(): Collection
    {
        return LabTestCategory::withCount('tests')->orderBy('name')->get();
    }

    public function getActiveCategories(): Collection
    {
        return LabTestCategory::active()->with('activeTests.criteria')->orderBy('name')->get();
    }

    public function storeCategory(array $data): LabTestCategory
    {
        return LabTestCategory::create($data);
    }

    public function updateCategory(LabTestCategory $category, array $data): LabTestCategory
    {
        $category->update($data);
        return $category;
    }

    public function deleteCategory(LabTestCategory $category): bool
    {
        if ($category->tests()->exists()) {
            return false;
        }
        $category->delete();
        return true;
    }

    public function storeTest(array $data): LabTest
    {
        return DB::transaction(function () use ($data) {
            $criteria = $this->normalizeTestCriteria($data);
            unset($data['criteria']);

            if ($criteria->isNotEmpty()) {
                $data['normal_range'] = $criteria->first()['normal_range'] ?? null;
                $data['unit'] = $criteria->first()['unit'] ?? null;
            }

            $test = LabTest::create($data);
            $this->syncTestCriteria($test, $criteria);

            return $test->load('criteria');
        });
    }

    public function updateTest(LabTest $test, array $data): LabTest
    {
        return DB::transaction(function () use ($test, $data) {
            $criteria = $this->normalizeTestCriteria($data);
            unset($data['criteria']);

            if ($criteria->isNotEmpty()) {
                $data['normal_range'] = $criteria->first()['normal_range'] ?? null;
                $data['unit'] = $criteria->first()['unit'] ?? null;
            }

            $test->update($data);
            $this->syncTestCriteria($test, $criteria);

            return $test->load('criteria');
        });
    }

    protected function normalizeTestCriteria(array $data): Collection
    {
        $criteria = collect($data['criteria'] ?? [])
            ->filter(function ($criterion) {
                return filled($criterion['name'] ?? null)
                    || filled($criterion['normal_range'] ?? null)
                    || filled($criterion['unit'] ?? null);
            })
            ->values()
            ->map(function ($criterion, $index) {
                return [
                    'name' => ($criterion['name'] ?? null) ?: 'Result',
                    'normal_range' => $criterion['normal_range'] ?? null,
                    'unit' => $criterion['unit'] ?? null,
                    'sort_order' => $index,
                ];
            });

        if ($criteria->isEmpty() && (filled($data['normal_range'] ?? null) || filled($data['unit'] ?? null))) {
            $criteria->push([
                'name' => 'Result',
                'normal_range' => $data['normal_range'] ?? null,
                'unit' => $data['unit'] ?? null,
                'sort_order' => 0,
            ]);
        }

        return $criteria;
    }

    protected function syncTestCriteria(LabTest $test, Collection $criteria): void
    {
        $test->criteria()->delete();

        $criteria->each(function ($criterion) use ($test) {
            $test->criteria()->create($criterion);
        });
    }

    public function toggleTest(LabTest $test): LabTest
    {
        $test->update(['is_active' => !$test->is_active]);
        return $test;
    }

    /*
    |--------------------------------------------------------------------------
    | Lab Requests
    |--------------------------------------------------------------------------
    */

    public function getRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LabRequest::with([
            'patient',
            'requestedBy',
            'items.labTest.criteria',
            'items.service',
            'department',
            'targetDepartment',
            'visit.queueEntries.department',
        ])
            ->latest();

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        if (!empty($filters['target_department_id'])) {
            $query->where('target_department_id', $filters['target_department_id']);
        }

        if (!empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function createRequest(Visit $visit, array $items, array $data = []): LabRequest
    {
        return DB::transaction(function () use ($visit, $items, $data) {
            if ($duplicate = $this->findNaturalDuplicateRequest($visit, $items, $data)) {
                return $duplicate->fresh(['items.labTest', 'items.service', 'patient']);
            }

            $request = LabRequest::create([
                'request_number'       => LabRequest::generateRequestNumber(),
                'visit_id'             => $visit->id,
                'emergency_case_id'    => $data['emergency_case_id'] ?? null,
                'emergency_session_id' => $data['emergency_session_id'] ?? null,
                'medical_record_id'    => $data['medical_record_id'] ?? null,
                'consultation_route_id' => $data['consultation_route_id'] ?? null,
                'patient_id'           => $visit->patient_id,
                'requested_by'         => Auth::id(),
                'department_id'        => $visit->department_id,
                'target_department_id' => $data['target_department_id'] ?? null,
                'clinical_info'        => $data['clinical_info'] ?? null,
                'urgency'              => $data['urgency'] ?? 'routine',
                'is_emergency'         => (bool) ($data['is_emergency'] ?? (($data['urgency'] ?? null) === 'emergency')),
                'status'               => 'pending',
            ]);

            foreach ($items as $item) {
                if (is_array($item)) {
                    // Structured item — supports service_id, lab_test_id and free-text name
                    $request->items()->create([
                        'lab_test_id' => $item['lab_test_id'] ?? null,
                        'service_id'  => $item['service_id'] ?? null,
                        'name'        => $item['name'] ?? null,
                        'status'      => $item['status'] ?? 'pending',
                    ]);
                } elseif (is_numeric($item)) {
                    // Item is a test-catalog ID
                    $request->items()->create([
                        'lab_test_id' => (int) $item,
                        'status'      => 'pending',
                    ]);
                } else {
                    // Item is a free-text description (non-catalog dept)
                    $request->items()->create([
                        'name'   => $item,
                        'status' => 'pending',
                    ]);
                }
            }

            $request->load(['items.labTest', 'patient']);

            LabRequestCreated::dispatch($request);

            app(VisitPathwayService::class)->record($visit, 'INVESTIGATION_REQUESTED', [
                'source' => $request,
                'department_id' => $request->target_department_id ?? $request->department_id,
                'title' => 'Investigation requested',
                'description' => $request->request_number,
            ]);

            // Activity log for direct OPD / emergency requests. Consultation-created
            // requests are already logged as a CONSULTATION investigation entry, so
            // skip those to avoid duplication.
            if (! $request->consultation_route_id) {
                $names = $request->items->map(fn ($i) => $i->name ?? $i->service?->name ?? $i->labTest?->name)->filter()->implode(', ');
                app(\App\Services\ActivityLogService::class)->log(
                    \App\Enums\LogModule::INVESTIGATION,
                    'INVESTIGATION_REQUESTED',
                    $request->toActivityContext() + ['metadata' => ['urgency' => $request->urgency]],
                    $request,
                    'Investigation requested: ' . ($names ?: $request->request_number),
                );
            }

            return $request;
        });
    }

    private function findNaturalDuplicateRequest(Visit $visit, array $items, array $data): ?LabRequest
    {
        $signature = $this->requestItemsSignature($items);

        return LabRequest::query()
            ->with('items')
            ->where('visit_id', $visit->id)
            ->where('consultation_route_id', $data['consultation_route_id'] ?? null)
            ->where('target_department_id', $data['target_department_id'] ?? null)
            ->where('requested_by', Auth::id())
            ->where('created_at', '>=', now()->subMinute())
            ->latest('id')
            ->get()
            ->first(fn (LabRequest $request) => $this->requestItemsSignature($request->items->map->toArray()->all()) === $signature);
    }

    private function requestItemsSignature(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => is_array($item)
                ? [
                    'lab_test_id' => (int) ($item['lab_test_id'] ?? 0),
                    'service_id' => (int) ($item['service_id'] ?? 0),
                    'name' => mb_strtolower(trim((string) ($item['name'] ?? ''))),
                ]
                : [
                    'lab_test_id' => is_numeric($item) ? (int) $item : 0,
                    'service_id' => 0,
                    'name' => is_numeric($item) ? '' : mb_strtolower(trim((string) $item)),
                ])
            ->sortBy(fn (array $item) => implode('|', $item))
            ->values()
            ->all();
    }

    public function getRequestDetails(LabRequest $request): LabRequest
    {
        return $request->load([
            'patient',
            'visit',
            'requestedBy',
            'department',
            'targetDepartment',
            'items.labTest.category',
            'items.labTest.criteria',
            'items.sample',
            'items.service.investigationHeaders.criteria',
            'items.service.investigationCriteria',
            'items.invoiceItem.invoice',
            'items.result.performedBy',
            'items.result.verifiedBy',
            'items.result.values',
            'samples.items',
            'samples.collectedBy',
            'samples.receivedBy',
        ]);
    }

    public function acceptRequest(LabRequest $request): LabRequest
    {
        if ($request->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be accepted.');
        }

        $request->update(['status' => 'processing']);
        $request->items()->where('status', 'pending')->update(['status' => 'processing']);

        if ($request->visit) {
            app(VisitPathwayService::class)->record($request->visit, 'INVESTIGATION_ACCEPTED', [
                'source' => $request,
                'department_id' => $request->target_department_id ?? $request->department_id,
                'title' => 'Investigation accepted',
                'description' => $request->request_number,
            ]);
        }

        app(\App\Services\ActivityLogService::class)->log(
            \App\Enums\LogModule::INVESTIGATION,
            'INVESTIGATION_ACCEPTED',
            $request->toActivityContext(),
            $request,
            'Investigation accepted: ' . $request->request_number,
        );

        return $request;
    }

    public function cancelRequest(LabRequest $request): LabRequest
    {
        if (in_array($request->status, ['completed', 'cancelled'])) {
            throw new \RuntimeException('This request cannot be cancelled.');
        }

        $request->update(['status' => 'cancelled']);
        $request->items()
            ->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'cancelled']);

        app(\App\Services\ActivityLogService::class)->log(
            \App\Enums\LogModule::INVESTIGATION,
            'INVESTIGATION_CANCELLED',
            $request->toActivityContext(),
            $request,
            'Investigation cancelled: ' . $request->request_number,
        );

        return $request;
    }

    /*
    |--------------------------------------------------------------------------
    | Lab Results
    |--------------------------------------------------------------------------
    */

    public function enterResult(LabRequestItem $item, array $data): LabResult
    {
        $existingResult = $item->result()->first();
        if ($existingResult?->is_verified) {
            throw new \RuntimeException('Verified investigation results cannot be edited.');
        }

        // Pay-before-results: an outpatient/walk-in must settle the bill before
        // results are entered. Emergency/inpatient run on a post-paid bill.
        $labRequest = $item->labRequest;
        if ($labRequest && $labRequest->requiresPrepaidResults()) {
            $paymentDecision = $this->paymentGate->policyForLabResultEntry($item, Auth::user());
            if (! $paymentDecision->allowed) {
                throw new \RuntimeException($paymentDecision->message);
            }
        }

        // Specimen gate: when a sample is tracked for this item, it must be
        // received in the lab before a result can be entered.
        if ($item->isBlockedBySample()) {
            throw new \RuntimeException(__('samples.errors.result_blocked'));
        }

        return DB::transaction(function () use ($item, $data) {
            $resultType = ResultType::tryFrom($data['result_type'] ?? 'parameters')
                ?? ResultType::PARAMETERS;

            $payload = [
                'lab_request_id'      => $item->lab_request_id,
                'result_type'         => $resultType->value,
                'is_abnormal'         => $data['is_abnormal'] ?? false,
                'remarks'             => $data['remarks'] ?? null,
                'performed_by'        => Auth::id(),
                'performed_at'        => now(),
            ];

            // Configurable overall result (canonical storage). Copied through only
            // when the controller has resolved them; legacy callers are unaffected.
            foreach ([
                'overall_result_type', 'overall_result_text', 'overall_result_numeric',
                'overall_result_boolean', 'overall_result_outcome', 'overall_result_unit',
            ] as $overallKey) {
                if (array_key_exists($overallKey, $data)) {
                    $payload[$overallKey] = $data[$overallKey];
                }
            }

            if ($resultType === ResultType::RICHTEXT) {
                $payload['result_text'] = $data['result_text'] ?? null;
            } elseif ($resultType->isFileBased()) {
                if (isset($data['result_file']) && $data['result_file'] instanceof UploadedFile) {
                    $path = $data['result_file']->store('investigation-results', 'public');
                    $payload['result_file']      = $path;
                    $payload['result_file_name'] = $data['result_file']->getClientOriginalName();
                }
            } else {
                $payload['result_value'] = $data['result_value'] ?? null;
            }

            // Optional attachment can accompany ANY result type (image / document
            // upload is always available alongside parameters or rich text).
            if (
                ! $resultType->isFileBased()
                && isset($data['result_file'])
                && $data['result_file'] instanceof UploadedFile
            ) {
                $path = $data['result_file']->store('investigation-results', 'public');
                $payload['result_file']      = $path;
                $payload['result_file_name'] = $data['result_file']->getClientOriginalName();
            }

            $result = LabResult::updateOrCreate(
                ['lab_request_item_id' => $item->id],
                $payload
            );

            $item->update(['status' => 'completed']);

            // Auto-update request status
            $this->updateRequestStatus($item->labRequest);

            if ($result->wasRecentlyCreated && $item->labRequest?->visit) {
                app(VisitPathwayService::class)->record($item->labRequest->visit, 'INVESTIGATION_RESULT_READY', [
                    'source' => $result,
                    'department_id' => $item->labRequest->target_department_id ?? $item->labRequest->department_id,
                    'title' => 'Investigation result ready',
                    'description' => $item->name ?? $item->service?->name ?? $item->labTest?->name,
                ]);
            }

            $testName = $item->name ?? $item->service?->name ?? $item->labTest?->name;
            $context = ($item->labRequest?->toActivityContext() ?? []) + array_filter([
                'investigation_result_id' => $result->id,
                'service_id' => $item->service_id,
                'invoice_item_id' => $item->invoice_item_id,
            ], fn ($v) => $v !== null);

            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::INVESTIGATION,
                $result->wasRecentlyCreated ? 'RESULT_ENTERED' : 'RESULT_UPDATED',
                $context + ['new_values' => array_filter([
                    'result' => $result->result_value ?: (\Illuminate\Support\Str::limit((string) $result->result_text, 120) ?: $result->result_file_name),
                    'is_abnormal' => $result->is_abnormal,
                ], fn ($v) => $v !== null && $v !== '')],
                $result,
                ($result->wasRecentlyCreated ? 'Result entered: ' : 'Result updated: ') . ($testName ?: $item->labRequest?->request_number),
            );

            return $result;
        });
    }

    public function verifyResult(LabResult $result): LabResult
    {
        if ($result->is_verified) {
            throw new \RuntimeException('This result has already been verified.');
        }

        $result->update([
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $result->loadMissing('requestItem.labRequest.visit');
        $request = $result->requestItem?->labRequest;
        if ($request?->visit) {
            app(VisitPathwayService::class)->record($request->visit, 'INVESTIGATION_VERIFIED', [
                'source' => $result,
                'department_id' => $request->target_department_id ?? $request->department_id,
                'title' => 'Investigation result verified',
                'description' => $request->request_number,
            ]);
        }

        if ($request) {
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::INVESTIGATION,
                'RESULT_VERIFIED',
                $request->toActivityContext() + array_filter([
                    'investigation_result_id' => $result->id,
                    'service_id' => $result->requestItem?->service_id,
                ], fn ($v) => $v !== null),
                $result,
                'Result verified: ' . ($result->requestItem?->name ?: $request->request_number),
            );
        }

        return $result;
    }

    public function batchEnterResults(LabRequest $request, array $results): LabRequest
    {
        return DB::transaction(function () use ($request, $results) {
            foreach ($results as $itemId => $data) {
                $item = $request->items()->findOrFail($itemId);
                if (!empty($data['result_value'])) {
                    $this->enterResult($item, $data);
                }
            }

            return $request->fresh(['items.labTest.criteria', 'items.result']);
        });
    }

    public function getResults(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LabResult::with([
            'requestItem.labTest.criteria',
            'labRequest.patient',
            'performedBy',
            'verifiedBy',
        ])->latest('performed_at');

        if (!empty($filters['verified']) && $filters['verified'] === 'yes') {
            $query->whereNotNull('verified_by');
        } elseif (!empty($filters['verified']) && $filters['verified'] === 'no') {
            $query->whereNull('verified_by');
        }

        if (!empty($filters['search'])) {
            $query->whereHas('labRequest', function ($q) use ($filters) {
                $q->search($filters['search']);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getResultRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $acceptedStatuses = ['accepted', 'processing', 'completed', 'verified'];

        $query = LabRequest::with([
            'patient',
            'requestedBy',
            'targetDepartment',
            'items.labTest',
            'items.service',
            'items.result.verifiedBy',
        ])
            ->whereHas('items', function ($q) use ($acceptedStatuses) {
                $q->whereIn('status', $acceptedStatuses)
                    ->orWhereNotNull('accepted_at')
                    ->orWhereNotNull('billed_at')
                    ->orWhereNotNull('invoice_item_id');
            })
            ->where('status', '!=', 'cancelled')
            ->latest('updated_at');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending_result') {
                $query->whereHas('items', function ($q) {
                    $q->whereIn('status', ['accepted', 'processing'])
                        ->whereDoesntHave('result');
                });
            } elseif ($filters['status'] === 'entered') {
                $query->whereHas('items.result')
                    ->whereHas('items', function ($q) {
                        $q->whereIn('status', ['accepted', 'processing', 'completed', 'verified']);
                    });
            } else {
                $query->byStatus($filters['status']);
            }
        }

        if (!empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        if (!empty($filters['target_department_id'])) {
            $query->where('target_department_id', $filters['target_department_id']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function updateRequestStatus(LabRequest $request): void
    {
        $request->load('items');

        $total = $request->items->count();
        $completed = $request->items->where('status', 'completed')->count();

        if ($total > 0 && $completed === $total) {
            $request->update(['status' => 'completed']);
            LabResultsCompleted::dispatch($request);
        } elseif ($completed > 0) {
            $request->update(['status' => 'processing']);
        }
    }

    public function getLabStats(array $filters = []): array
    {
        $scope = function ($query) use ($filters) {
            return $query
                ->when($filters['requested_by'] ?? null, fn ($q, $userId) => $q->where('requested_by', $userId))
                ->when($filters['target_department_id'] ?? null, fn ($q, $departmentId) => $q->where('target_department_id', $departmentId));
        };

        return [
            'pending'         => $scope(LabRequest::pending())->count(),
            'processing'      => $scope(LabRequest::processing())->count(),
            'completed_today' => $scope(LabRequest::completed()->whereDate('updated_at', today()))->count(),
            'total_tests'     => LabTest::active()->count(),
        ];
    }

    public function getInvestigationDepartments(): Collection
    {
        return Department::acceptsRequests()->orderBy('name')->get();
    }

    public function getVisitLabRequests(Visit $visit): Collection
    {
        return $visit->labRequests()
            ->with(['targetDepartment', 'department', 'requestedBy', 'items.labTest', 'items.service.department', 'items.result'])
            ->latest()
            ->get();
    }
}

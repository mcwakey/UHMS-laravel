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
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Storage;

class LabService
{
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
        $query = LabRequest::with(['patient', 'requestedBy', 'items.labTest.criteria', 'department', 'targetDepartment'])
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
            $request = LabRequest::create([
                'request_number'       => LabRequest::generateRequestNumber(),
                'visit_id'             => $visit->id,
                'patient_id'           => $visit->patient_id,
                'requested_by'         => Auth::id(),
                'department_id'        => $visit->department_id,
                'target_department_id' => $data['target_department_id'] ?? null,
                'clinical_info'        => $data['clinical_info'] ?? null,
                'urgency'              => $data['urgency'] ?? 'routine',
                'status'               => 'pending',
            ]);

            foreach ($items as $item) {
                if (is_numeric($item)) {
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

            return $request;
        });
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
            'items.result.performedBy',
            'items.result.verifiedBy',
        ]);
    }

    public function acceptRequest(LabRequest $request): LabRequest
    {
        if ($request->status !== 'pending') {
            throw new \RuntimeException('Only pending requests can be accepted.');
        }

        $request->update(['status' => 'processing']);
        $request->items()->where('status', 'pending')->update(['status' => 'processing']);

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

        return $request;
    }

    /*
    |--------------------------------------------------------------------------
    | Lab Results
    |--------------------------------------------------------------------------
    */

    public function enterResult(LabRequestItem $item, array $data): LabResult
    {
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

            $result = LabResult::updateOrCreate(
                ['lab_request_item_id' => $item->id],
                $payload
            );

            $item->update(['status' => 'completed']);

            // Auto-update request status
            $this->updateRequestStatus($item->labRequest);

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

    public function getLabStats(): array
    {
        return [
            'pending'         => LabRequest::pending()->count(),
            'processing'      => LabRequest::processing()->count(),
            'completed_today' => LabRequest::completed()->whereDate('updated_at', today())->count(),
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
            ->with(['items.labTest', 'items.result', 'requestedBy'])
            ->latest()
            ->get();
    }
}

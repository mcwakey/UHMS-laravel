<?php

namespace App\Services;

use App\Events\LabRequestCreated;
use App\Events\LabResultsCompleted;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\Visit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        return LabTestCategory::active()->with('activeTests')->orderBy('name')->get();
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
        return LabTest::create($data);
    }

    public function updateTest(LabTest $test, array $data): LabTest
    {
        $test->update($data);
        return $test;
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
        $query = LabRequest::with(['patient', 'requestedBy', 'items.labTest', 'department'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
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

    public function createRequest(Visit $visit, array $testIds, array $data = []): LabRequest
    {
        return DB::transaction(function () use ($visit, $testIds, $data) {
            $request = LabRequest::create([
                'request_number' => LabRequest::generateRequestNumber(),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'requested_by' => Auth::id(),
                'department_id' => $visit->department_id,
                'clinical_info' => $data['clinical_info'] ?? null,
                'urgency' => $data['urgency'] ?? 'routine',
                'status' => 'pending',
            ]);

            foreach ($testIds as $testId) {
                $request->items()->create([
                    'lab_test_id' => $testId,
                    'status' => 'pending',
                ]);
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
            'items.labTest.category',
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
            $result = LabResult::updateOrCreate(
                ['lab_request_item_id' => $item->id],
                [
                    'lab_request_id' => $item->lab_request_id,
                    'result_value' => $data['result_value'],
                    'is_abnormal' => $data['is_abnormal'] ?? false,
                    'remarks' => $data['remarks'] ?? null,
                    'performed_by' => Auth::id(),
                    'performed_at' => now(),
                ]
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

            return $request->fresh(['items.labTest', 'items.result']);
        });
    }

    public function getResults(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LabResult::with([
            'requestItem.labTest',
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
            'pending' => LabRequest::pending()->count(),
            'processing' => LabRequest::processing()->count(),
            'completed_today' => LabRequest::completed()->whereDate('updated_at', today())->count(),
            'total_tests' => LabTest::active()->count(),
        ];
    }

    public function getVisitLabRequests(Visit $visit): Collection
    {
        return $visit->labRequests()
            ->with(['items.labTest', 'items.result', 'requestedBy'])
            ->latest()
            ->get();
    }
}

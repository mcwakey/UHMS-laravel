<?php

namespace App\Services\Analyzer;

use App\Models\Analyzer;
use App\Models\AnalyzerRawMessage;
use App\Models\AnalyzerTestMapping;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResult;
use App\Services\LabService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultDispatchService
{
    public function __construct(
        protected LabService $labService
    ) {}

    /**
     * Process parsed results and dispatch them to matching lab orders.
     *
     * @param  AnalyzerRawMessage  $message   The raw message record
     * @param  array               $parsed    Parsed output from HL7/ASTM parser
     * @return array{matched: int, unmatched: int, errors: array}
     */
    public function dispatch(AnalyzerRawMessage $message, array $parsed): array
    {
        $sampleId = $parsed['sample_id'] ?? null;
        $results = $parsed['results'] ?? [];

        $report = ['matched' => 0, 'unmatched' => 0, 'errors' => []];

        if (!$sampleId) {
            $report['errors'][] = 'No sample ID found in message.';
            return $report;
        }

        // Update the message with the extracted sample ID
        $message->update(['sample_id' => $sampleId]);

        // Find the lab request by sample ID
        $labRequest = LabRequest::where('sample_id', $sampleId)
            ->whereIn('status', ['pending', 'processing'])
            ->with('items.labTest')
            ->first();

        if (!$labRequest) {
            $report['errors'][] = "No active lab request found for sample ID: {$sampleId}";
            return $report;
        }

        // Ensure the request is at least in processing state
        if ($labRequest->status === 'pending') {
            $labRequest->update(['status' => 'processing']);
        }

        // Get the analyzer for test code mapping
        $analyzer = $message->analyzer;

        foreach ($results as $result) {
            try {
                $this->dispatchSingleResult($labRequest, $analyzer, $result, $report);
            } catch (\Throwable $e) {
                $report['errors'][] = "Error processing result for test code {$result['test_code']}: {$e->getMessage()}";
                Log::error('Analyzer result dispatch error', [
                    'message_id' => $message->id,
                    'test_code' => $result['test_code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $report;
    }

    /**
     * Dispatch a single result to the matching lab request item.
     */
    protected function dispatchSingleResult(
        LabRequest $labRequest,
        ?Analyzer $analyzer,
        array $result,
        array &$report
    ): void {
        $testCode = $result['test_code'];

        // Step 1: Map analyzer test code to lab test ID
        $labTestId = $this->resolveLabTestId($analyzer, $testCode);

        if (!$labTestId) {
            $report['unmatched']++;
            $report['errors'][] = "No mapping found for analyzer test code: {$testCode}";
            return;
        }

        // Step 2: Find the matching lab request item
        $item = $labRequest->items
            ->where('lab_test_id', $labTestId)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if (!$item) {
            $report['unmatched']++;
            $report['errors'][] = "No pending item found for lab test ID {$labTestId} (analyzer code: {$testCode})";
            return;
        }

        // Step 3: Prepare result data
        $resultValue = $result['result_value'];

        // Apply unit conversion if mapping exists
        if ($analyzer) {
            $mapping = $analyzer->testMappings()
                ->where('lab_test_id', $labTestId)
                ->first();

            if ($mapping) {
                $resultValue = $mapping->convertValue($resultValue);
            }
        }

        $isAbnormal = $this->isAbnormal($result['abnormal_flag'] ?? null);

        // Step 4: Enter the result using LabService
        // We need to authenticate as a system user for background processing
        $this->enterResultAsSystem($item, [
            'result_value' => (string) $resultValue,
            'is_abnormal' => $isAbnormal,
            'remarks' => $this->buildRemarks($result, $analyzer),
        ]);

        $report['matched']++;
    }

    /**
     * Resolve an analyzer test code to a lab_test_id.
     */
    protected function resolveLabTestId(?Analyzer $analyzer, string $testCode): ?int
    {
        // Try analyzer-specific mapping first
        if ($analyzer) {
            $mapping = $analyzer->mapTestCode($testCode);
            if ($mapping) {
                return $mapping->lab_test_id;
            }
        }

        // Fallback: try direct match on lab_tests.code
        $labTest = \App\Models\LabTest::where('code', $testCode)->active()->first();
        if ($labTest) {
            return $labTest->id;
        }

        return null;
    }

    /**
     * Enter a result without requiring an authenticated user session.
     *
     * Uses a direct DB approach since LabService::enterResult() relies on auth()->id().
     */
    protected function enterResultAsSystem(LabRequestItem $item, array $data): void
    {
        DB::transaction(function () use ($item, $data) {
            // Find or create a system user ID for analyzer results
            $systemUserId = $this->getSystemUserId();

            LabResult::updateOrCreate(
                ['lab_request_item_id' => $item->id],
                [
                    'lab_request_id' => $item->lab_request_id,
                    'result_value' => $data['result_value'],
                    'is_abnormal' => $data['is_abnormal'] ?? false,
                    'remarks' => $data['remarks'] ?? null,
                    'performed_by' => $systemUserId,
                    'performed_at' => now(),
                ]
            );

            $item->update(['status' => 'completed']);

            // Check if all items are completed and update request status
            $request = $item->labRequest()->with('items')->first();
            $total = $request->items->count();
            $completed = $request->items->where('status', 'completed')->count();

            if ($total > 0 && $completed === $total) {
                $request->update(['status' => 'completed']);
                \App\Events\LabResultsCompleted::dispatch($request);
            } elseif ($completed > 0) {
                $request->update(['status' => 'processing']);
            }
        });
    }

    /**
     * Get or create a system user ID for automated results.
     */
    protected function getSystemUserId(): int
    {
        // Use the first Super Admin user as the system user
        $systemUser = \App\Models\User::role('Super Admin')->first();

        if ($systemUser) {
            return $systemUser->id;
        }

        // Fallback: use user ID 1
        return 1;
    }

    /**
     * Determine if a result is abnormal based on the flag.
     */
    protected function isAbnormal(?string $flag): bool
    {
        if (empty($flag)) {
            return false;
        }

        $abnormalFlags = ['H', 'L', 'HH', 'LL', 'A', '+', '-', 'AA', 'U', 'D'];

        return in_array(strtoupper(trim($flag)), $abnormalFlags, true);
    }

    /**
     * Build a remarks string from the analyzer result.
     */
    protected function buildRemarks(array $result, ?Analyzer $analyzer): string
    {
        $parts = ['[Auto] Analyzer result'];

        if ($analyzer) {
            $parts[0] .= " from {$analyzer->name}";
        }

        if (!empty($result['unit'])) {
            $parts[] = "Unit: {$result['unit']}";
        }

        if (!empty($result['reference_range'])) {
            $parts[] = "Ref: {$result['reference_range']}";
        }

        if (!empty($result['abnormal_flag'])) {
            $parts[] = "Flag: {$result['abnormal_flag']}";
        }

        return implode(' | ', $parts);
    }
}

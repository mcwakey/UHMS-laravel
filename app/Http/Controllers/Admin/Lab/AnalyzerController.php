<?php

namespace App\Http\Controllers\Admin\Lab;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyzerMessage;
use App\Models\Analyzer;
use App\Models\AnalyzerRawMessage;
use App\Models\AnalyzerTestMapping;
use App\Services\Analyzer\AnalyzerService;
use Illuminate\Http\Request;

class AnalyzerController extends Controller
{
    public function __construct(
        protected AnalyzerService $analyzerService
    ) {}

    /**
     * Analyzer device listing.
     */
    public function index(Request $request)
    {
        $analyzers = $this->analyzerService->listAnalyzers($request->only(['status', 'protocol', 'search']));
        $stats = $this->analyzerService->getAnalyzerStats();

        return view('admin.analyzers.index', compact('analyzers', 'stats'));
    }

    /**
     * Store a new analyzer device.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'model' => 'nullable|string|max:191',
            'manufacturer' => 'nullable|string|max:191',
            'protocol' => 'required|in:hl7,astm',
            'connection_type' => 'required|in:tcp,serial',
            'ip_address' => 'nullable|ip|required_if:connection_type,tcp',
            'port' => 'nullable|integer|min:1|max:65535|required_if:connection_type,tcp',
            'com_port' => 'nullable|string|max:20|required_if:connection_type,serial',
            'baud_rate' => 'nullable|integer|min:300|max:115200',
        ]);

        $this->analyzerService->createAnalyzer($validated);

        return back()->with('success', __('messages.analyzers.created'));
    }

    /**
     * Update an analyzer device.
     */
    public function update(Request $request, Analyzer $analyzer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'model' => 'nullable|string|max:191',
            'manufacturer' => 'nullable|string|max:191',
            'protocol' => 'required|in:hl7,astm',
            'connection_type' => 'required|in:tcp,serial',
            'ip_address' => 'nullable|ip|required_if:connection_type,tcp',
            'port' => 'nullable|integer|min:1|max:65535|required_if:connection_type,tcp',
            'com_port' => 'nullable|string|max:20|required_if:connection_type,serial',
            'baud_rate' => 'nullable|integer|min:300|max:115200',
        ]);

        $this->analyzerService->updateAnalyzer($analyzer, $validated);

        return back()->with('success', __('messages.analyzers.updated'));
    }

    /**
     * Toggle analyzer active status.
     */
    public function toggle(Analyzer $analyzer)
    {
        $this->analyzerService->toggleAnalyzer($analyzer);
        $status = $analyzer->fresh()->is_active ? 'activated' : 'deactivated';

        return back()->with('success', __('messages.analyzers.status_changed', ['name' => $analyzer->name, 'status' => $status]));
    }

    /**
     * Delete an analyzer device.
     */
    public function destroy(Analyzer $analyzer)
    {
        if (!$this->analyzerService->deleteAnalyzer($analyzer)) {
            return back()->with('error', __('messages.analyzers.cannot_delete_processing'));
        }

        return back()->with('success', __('messages.analyzers.deleted'));
    }

    /**
     * Show analyzer detail with test mappings.
     */
    public function show(Analyzer $analyzer)
    {
        $analyzer = $this->analyzerService->getAnalyzer($analyzer);
        $mappings = $this->analyzerService->getMappings($analyzer);
        $labTests = $this->analyzerService->getAvailableLabTests();
        $recentMessages = $analyzer->rawMessages()->latest('received_at')->limit(20)->get();

        return view('admin.analyzers.show', compact('analyzer', 'mappings', 'labTests', 'recentMessages'));
    }

    /**
     * Store a test code mapping.
     */
    public function storeMapping(Request $request, Analyzer $analyzer)
    {
        $validated = $request->validate([
            'analyzer_test_code' => 'required|string|max:50',
            'lab_test_id' => 'required|exists:lab_tests,id',
            'unit_conversion_factor' => 'nullable|numeric|min:0.0001|max:9999.9999',
        ]);

        $validated['unit_conversion_factor'] = $validated['unit_conversion_factor'] ?? 1.0000;

        $this->analyzerService->storeMapping($analyzer, $validated);

        return back()->with('success', __('messages.analyzers.mapping_added'));
    }

    /**
     * Update a test code mapping.
     */
    public function updateMapping(Request $request, AnalyzerTestMapping $mapping)
    {
        $validated = $request->validate([
            'analyzer_test_code' => 'required|string|max:50',
            'lab_test_id' => 'required|exists:lab_tests,id',
            'unit_conversion_factor' => 'nullable|numeric|min:0.0001|max:9999.9999',
        ]);

        $this->analyzerService->updateMapping($mapping, $validated);

        return back()->with('success', __('messages.analyzers.mapping_updated'));
    }

    /**
     * Delete a test code mapping.
     */
    public function destroyMapping(AnalyzerTestMapping $mapping)
    {
        $this->analyzerService->deleteMapping($mapping);

        return back()->with('success', __('messages.analyzers.mapping_removed'));
    }

    /**
     * Message diagnostics / log viewer.
     */
    public function diagnostics(Request $request)
    {
        $messages = $this->analyzerService->getMessages(
            $request->only(['analyzer_id', 'status', 'protocol', 'sample_id'])
        );
        $msgStats = $this->analyzerService->getMessageStats();
        $analyzers = Analyzer::orderBy('name')->get();

        return view('admin.analyzers.diagnostics', compact('messages', 'msgStats', 'analyzers'));
    }

    /**
     * Reprocess a failed message.
     */
    public function reprocess(AnalyzerRawMessage $message)
    {
        if (!in_array($message->processing_status, ['failed', 'received'])) {
            return back()->with('error', __('messages.analyzers.cannot_reprocess'));
        }

        $message->update([
            'processing_status' => 'received',
            'processing_attempts' => 0,
            'error_message' => null,
        ]);

        ProcessAnalyzerMessage::dispatch($message->id);

        return back()->with('success', __('messages.analyzers.message_requeued', ['id' => $message->id]));
    }
}

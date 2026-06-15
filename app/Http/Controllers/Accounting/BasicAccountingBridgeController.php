<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\FinancialEntry;
use App\Services\ActivityLogService;
use App\Services\BasicAccountingBackfillService;
use Illuminate\Http\Request;

class BasicAccountingBridgeController extends Controller
{
    public function __construct(
        protected BasicAccountingBackfillService $backfill,
        protected ActivityLogService $activityLog,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['from', 'to', 'entry_type', 'category_id', 'entry_id']);
        $preview = $request->boolean('preview') ? $this->backfill->preview($filters) : null;
        if ($preview) {
            $this->activityLog->log(LogModule::ACCOUNTING, 'BASIC_ENTRY_POSTING_BATCH_PREVIEWED', [
                'severity' => LogSeverity::NOTICE,
                'causer' => $request->user(),
                'metadata' => ['filters' => $filters, 'eligible' => $preview['eligible'], 'ineligible' => $preview['ineligible']],
            ], null, 'Basic Accounting posting batch previewed');
        }

        return view('accounting.basic-bridge.index', [
            'preview' => $preview,
            'categories' => AccountCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function execute(Request $request)
    {
        $data = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:financial_entries,id'],
            'confirmation' => ['accepted'],
        ]);
        $totals = $this->backfill->execute(['entry_id' => $data['entry_ids']], $request->user());
        $this->activityLog->log(LogModule::ACCOUNTING, 'BASIC_ENTRY_POSTING_BATCH_EXECUTED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $request->user(),
            'metadata' => $totals,
        ], null, 'Basic Accounting posting batch executed');

        return back()->with('success', __('accounting.batch_posting_complete', $totals));
    }
}

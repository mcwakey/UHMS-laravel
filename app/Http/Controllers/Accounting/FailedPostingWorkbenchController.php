<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\AccountingPostingAttempt;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPostingHandlerRegistry;
use App\Services\AccountingPostingSourceLinkService;
use App\Services\ActivityLogService;
use App\Services\FailedPostingWorkbenchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FailedPostingWorkbenchController extends Controller
{
    public function __construct(
        protected FailedPostingWorkbenchService $workbench,
        protected AccountingPostingHandlerRegistry $handlers,
        protected AccountingPostingSourceLinkService $sourceLinks,
        protected ActivityLogService $activityLog,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'status', 'source_module', 'source_type', 'posting_type', 'date_from', 'date_to',
            'attempt_count', 'error_code', 'has_journal', 'has_reversal', 'resolved_by', 'waived_only',
        ]);

        return view('accounting.failed-postings.index', [
            'attempts' => $this->workbench->query($filters)->paginate(25)->withQueryString(),
            'dashboard' => $this->workbench->dashboard(),
            'statuses' => AccountingPostingAttempt::STATUSES,
            'sourceModules' => AccountingPostingAttempt::query()->distinct()->orderBy('source_module')->pluck('source_module'),
            'sourceTypes' => AccountingPostingAttempt::query()->distinct()->orderBy('source_type')->pluck('source_type'),
            'postingTypes' => AccountingPostingAttempt::query()->distinct()->orderBy('posting_type')->pluck('posting_type'),
            'resolvers' => User::query()->whereIn('id', AccountingPostingAttempt::query()->whereNotNull('resolved_by')->pluck('resolved_by'))->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, AccountingPostingAttempt $failedPosting)
    {
        $failedPosting->load([
            'journalEntry', 'reversalJournalEntry', 'resolutionJournalEntry',
            'resolvedBy', 'createdBy', 'events.actor',
        ]);
        $this->activityLog->log(LogModule::ACCOUNTING, 'FAILED_POSTING_VIEWED', [
            'causer' => $request->user(),
            'source_type' => $failedPosting->source_type,
            'source_id' => $failedPosting->source_id,
            'metadata' => ['posting_attempt_id' => $failedPosting->id],
        ], $failedPosting, 'Failed posting viewed');

        return view('accounting.failed-postings.show', [
            'attempt' => $failedPosting,
            'sourceLink' => $this->sourceLinks->link($failedPosting),
            'supported' => $this->handlers->supports($failedPosting),
            'sourceSnapshot' => $this->prettyValue($failedPosting, 'source_snapshot'),
            'postingSnapshot' => $this->prettyValue($failedPosting, 'posting_snapshot'),
            'errorContext' => $this->prettyValue($failedPosting, 'error_context'),
            'resolutionTypes' => FailedPostingWorkbenchService::RESOLUTION_TYPES,
            'journals' => JournalEntry::query()->latest('entry_date')->limit(200)->get(['id', 'journal_number', 'description']),
        ]);
    }

    public function retry(Request $request, AccountingPostingAttempt $failedPosting)
    {
        $result = $this->workbench->retry($failedPosting, $request->user());

        return back()->with(
            $result->success ? 'success' : 'error',
            $result->success ? __('accounting.retry_succeeded') : ($result->message ?: __('accounting.retry_failed')),
        );
    }

    public function retrySelected(Request $request)
    {
        $data = $request->validate([
            'attempt_ids' => ['required', 'array', 'min:1'],
            'attempt_ids.*' => ['integer', 'exists:accounting_posting_attempts,id'],
        ]);
        $summary = $this->workbench->retrySelected($data['attempt_ids'], $request->user());

        return back()->with('success', __('accounting.retry_result', $summary));
    }

    public function resolve(Request $request, AccountingPostingAttempt $failedPosting)
    {
        $data = $request->validate([
            'resolution_type' => ['required', Rule::in(FailedPostingWorkbenchService::RESOLUTION_TYPES)],
            'resolution_note' => ['required', 'string', 'max:4000'],
            'resolution_journal_entry_id' => ['nullable', 'exists:journal_entries,id'],
            'resolution_source_type' => ['nullable', 'string', 'max:120'],
            'resolution_source_id' => ['nullable', 'integer', 'min:1'],
            'resolution_reference' => ['nullable', 'string', 'max:191'],
            'resolution_evidence' => ['nullable', 'string', 'max:10000'],
        ]);
        $this->workbench->resolve($failedPosting, $data, $request->user());

        return back()->with('success', __('accounting.posting_resolved'));
    }

    public function waive(Request $request, AccountingPostingAttempt $failedPosting)
    {
        $data = $request->validate([
            'waive_reason' => ['required', 'string', 'max:4000'],
            'materiality_note' => ['required', 'string', 'max:4000'],
            'waiver_review_date' => ['nullable', 'date'],
            'resolution_reference' => ['nullable', 'string', 'max:191'],
            'resolution_evidence' => ['nullable', 'string', 'max:10000'],
        ]);
        $this->workbench->waive($failedPosting, $data, $request->user());

        return back()->with('success', __('accounting.posting_waived'));
    }

    protected function prettyValue(AccountingPostingAttempt $attempt, string $field): string
    {
        $value = $attempt->{$field};
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        $raw = $attempt->getRawOriginal($field);
        if (! is_string($raw) || trim($raw) === '') {
            return '';
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE
            ? (json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: $raw)
            : $raw;
    }
}

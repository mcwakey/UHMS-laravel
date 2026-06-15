<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPostingAttempt;
use Illuminate\Http\Request;

class AccountingPostingAttemptController extends Controller
{
    public function index(Request $request)
    {
        $attempts = AccountingPostingAttempt::query()
            ->with('journalEntry')
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->string('status')))
            ->when($request->filled('source_module'), fn ($q) => $q->where('source_module', (string) $request->string('source_module')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.(string) $request->string('search').'%';
                $q->where(fn ($q) => $q
                    ->where('idempotency_key', 'like', $search)
                    ->orWhere('source_type', 'like', $search)
                    ->orWhere('error_message', 'like', $search));
            })
            ->latest('last_attempted_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.posting-attempts.index', [
            'attempts' => $attempts,
            'statuses' => AccountingPostingAttempt::STATUSES,
            'sourceModules' => AccountingPostingAttempt::query()->distinct()->orderBy('source_module')->pluck('source_module'),
        ]);
    }

    public function show(AccountingPostingAttempt $postingAttempt)
    {
        $postingAttempt->load(['journalEntry', 'reversalJournalEntry', 'resolvedBy', 'events.actor']);

        return view('accounting.posting-attempts.show', compact('postingAttempt'));
    }
}

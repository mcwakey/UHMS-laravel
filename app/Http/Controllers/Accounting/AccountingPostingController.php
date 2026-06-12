<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\AccountingPostingRetryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountingPostingController extends Controller
{
    public function retry(Request $request, AccountingPostingRetryService $service)
    {
        $data = $request->validate([
            'source_type' => ['required', Rule::in(['invoice', 'payment', 'discount', 'credit_note'])],
            'source_id' => ['required', 'integer', 'min:1'],
        ]);

        $entry = $service->retry($data['source_type'], (int) $data['source_id']);

        return back()->with(
            $entry ? 'success' : 'error',
            $entry
                ? __('messages.accounting.posting_retried', ['number' => $entry->journal_number])
                : __('messages.accounting.posting_retry_failed')
        );
    }
}

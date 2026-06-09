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
                ? "Accounting posting retried successfully ({$entry->journal_number})."
                : 'Accounting retry did not produce a journal entry. Review the posting status/error on the source record.'
        );
    }
}

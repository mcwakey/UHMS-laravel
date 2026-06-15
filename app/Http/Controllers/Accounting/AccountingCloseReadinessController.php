<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\AccountingCloseReadinessService;
use Illuminate\Http\Request;

class AccountingCloseReadinessController extends Controller
{
    public function __invoke(Request $request, AccountingCloseReadinessService $service)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->endOfMonth()->toDateString();

        return view('accounting.close-readiness', [
            'summary' => $service->summary($from, $to, $request->user()),
        ]);
    }
}

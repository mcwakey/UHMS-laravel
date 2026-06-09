<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingDashboardController extends Controller
{
    public function __invoke(Request $request, AccountingService $accountingService)
    {
        $stats = $accountingService->getAccountingDashboard(
            $request->input('date_from'),
            $request->input('date_to'),
        );

        return view('accounting.dashboard', compact('stats'));
    }
}

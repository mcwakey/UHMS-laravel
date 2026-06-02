<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BloodBankReportService;
use Illuminate\Http\Request;

class BloodBankReportController extends Controller
{
    public function __construct(private BloodBankReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'blood_group', 'component_type']);

        return view('blood-bank.reports', [
            'inventory' => $this->reports->inventory($filters),
            'requests' => $this->reports->requests($filters),
            'issues' => $this->reports->issues($filters),
            'filters' => $filters,
        ]);
    }
}

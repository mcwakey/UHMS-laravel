<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Http\Controllers\Controller;
use App\Services\EmergencyReportService;
use Illuminate\Http\Request;

class EmergencyReportController extends Controller
{
    public function __construct(private EmergencyReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'triage_category', 'disposition']);

        return view('emergency.reports', [
            'cases' => $this->reports->attendance($filters),
            'dispositionCounts' => $this->reports->dispositionCounts($filters),
            'filters' => $filters,
        ]);
    }
}

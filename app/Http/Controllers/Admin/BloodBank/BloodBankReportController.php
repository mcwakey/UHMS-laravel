<?php

namespace App\Http\Controllers\Admin\BloodBank;

use App\Http\Controllers\Controller;
use App\Services\BloodBankReportService;
use Illuminate\Http\Request;

class BloodBankReportController extends Controller
{
    public function __construct(private BloodBankReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'blood_group', 'component_type', 'decision', 'result']);

        return view('blood-bank.reports', [
            'summary' => $this->reports->summary(),
            'inventory' => $this->reports->inventory($filters),
            'requests' => $this->reports->requests($filters),
            'issues' => $this->reports->issues($filters),
            'donorScreenings' => $this->reports->donorScreenings($filters),
            'diseaseScreenings' => $this->reports->infectiousDiseaseScreening($filters),
            'crossmatches' => $this->reports->crossmatches($filters),
            'reactions' => $this->reports->transfusionReactions($filters),
            'filters' => $filters,
        ]);
    }
}

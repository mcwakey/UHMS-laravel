<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\StockLocation;
use App\Models\Visit;
use App\Services\MarChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MarChartController extends Controller
{
    public function __construct(private MarChartService $charts) {}

    public function admission(Request $request, Admission $admission)
    {
        $chart = $this->charts->buildForAdmission($admission, $this->selectedDate($request));

        return $this->respond($request, $chart, $this->stockLocationsForAdmission($admission));
    }

    public function emergency(Request $request, Visit $visit)
    {
        abort_unless(
            $visit->status === VisitStatus::EMERGENCY || $visit->visit_type === VisitType::EMERGENCY,
            404
        );

        $chart = $this->charts->buildForEmergencyVisit($visit, $this->selectedDate($request));

        return $this->respond($request, $chart, $this->stockLocationsForEmergency());
    }

    public function visit(Request $request, Visit $visit)
    {
        $chart = $this->charts->buildForPatientVisit($visit, $this->selectedDate($request));
        $stockLocations = $visit->admission
            ? $this->stockLocationsForAdmission($visit->admission)
            : $this->stockLocationsForEmergency();

        return $this->respond($request, $chart, $stockLocations);
    }

    private function selectedDate(Request $request): Carbon
    {
        try {
            return Carbon::parse($request->query('date', today()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            return today()->startOfDay();
        }
    }

    private function respond(Request $request, array $chart, $stockLocations)
    {
        $viewData = [
            'chart' => $chart,
            'stockLocations' => $stockLocations,
        ];

        if ($request->header('X-Mar-Partial') === 'chart') {
            return view('medication-administration.partials.mar-chart-content', $viewData);
        }

        return view('medication-administration.mar-chart', $viewData);
    }

    private function stockLocationsForAdmission(Admission $admission)
    {
        $admission->loadMissing('bed.ward');

        return StockLocation::active()
            ->where(function ($query) use ($admission) {
                $query->where('type', 'ward')
                    ->orWhere('department_id', $admission->bed?->ward?->department_id);
            })
            ->orderBy('name')
            ->get();
    }

    private function stockLocationsForEmergency()
    {
        return StockLocation::active()
            ->whereIn('type', ['emergency', 'ward'])
            ->orderBy('name')
            ->get();
    }
}
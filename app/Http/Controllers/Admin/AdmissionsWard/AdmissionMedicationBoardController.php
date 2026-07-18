<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\StockLocation;
use App\Services\AdmissionMedicationBoardService;
use App\Services\WardService;
use Illuminate\Http\Request;

class AdmissionMedicationBoardController extends Controller
{
    public function __construct(
        private AdmissionMedicationBoardService $board,
        private WardService $wards,
    ) {}

    public function index(Request $request)
    {
        $rows = $this->board->index($request->only('ward_id'));
        $wards = $this->wards->activeWards();

        return view('medication-administration.admission-board', [
            'rows' => $rows,
            'wards' => $wards,
            'filters' => $request->only('ward_id'),
        ]);
    }

    public function show(Admission $admission)
    {
        $payload = $this->board->forAdmission($admission);
        $stockLocations = StockLocation::active()
            ->where(function ($query) use ($admission) {
                $query->where('type', 'ward')
                    ->orWhere('department_id', $admission->bed?->ward?->department_id);
            })
            ->orderBy('name')
            ->get();

        return view('medication-administration.admission-show', array_merge($payload, [
            'stockLocations' => $stockLocations,
        ]));
    }
}

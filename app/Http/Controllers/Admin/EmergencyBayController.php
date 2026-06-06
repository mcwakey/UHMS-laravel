<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyBay;
use App\Models\EmergencyCase;
use App\Services\EmergencyBayService;
use Illuminate\Http\Request;

class EmergencyBayController extends Controller
{
    public function __construct(private EmergencyBayService $bays) {}

    public function index()
    {
        return view('emergency.bays', [
            'bays' => EmergencyBay::with('activeCase.patient')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:emergency_bays,code'],
            'bay_type' => ['required', 'in:RESUSCITATION,OBSERVATION,TREATMENT,MINOR_PROCEDURE,ISOLATION,WAITING_AREA,EMERGENCY_WARD'],
            'status' => ['nullable', 'in:AVAILABLE,OCCUPIED,CLEANING,OUT_OF_SERVICE,RESERVED'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        EmergencyBay::create($data + ['is_active' => true]);

        return back()->with('success', 'Emergency bay created.');
    }

    public function assign(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'emergency_bay_id' => ['required', 'exists:emergency_bays,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'bed_id' => ['nullable', 'exists:beds,id'],
            'override' => ['nullable', 'boolean'],
        ]);

        $this->bays->assign(
            $emergencyCase,
            (int) $data['emergency_bay_id'],
            $request->user(),
            (bool) ($data['override'] ?? false),
            isset($data['ward_id']) ? (int) $data['ward_id'] : null,
            isset($data['bed_id']) ? (int) $data['bed_id'] : null,
        );

        return back()->with('success', 'Emergency bay assigned.');
    }

    public function assignWardBed(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'ward_id' => ['nullable', 'exists:wards,id'],
            'bed_id' => ['nullable', 'exists:beds,id'],
            'override' => ['nullable', 'boolean'],
        ]);

        $this->bays->assignWardBed(
            $emergencyCase,
            isset($data['ward_id']) ? (int) $data['ward_id'] : null,
            isset($data['bed_id']) ? (int) $data['bed_id'] : null,
            $request->user(),
            (bool) ($data['override'] ?? false),
        );

        return back()->with('success', 'Ward / bed updated.');
    }
}

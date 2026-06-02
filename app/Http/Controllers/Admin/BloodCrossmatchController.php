<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Services\BloodCrossmatchService;
use Illuminate\Http\Request;

class BloodCrossmatchController extends Controller
{
    public function __construct(private BloodCrossmatchService $crossmatches) {}

    public function store(Request $request, BloodRequest $bloodRequest)
    {
        $data = $request->validate([
            'blood_unit_id' => ['required', 'exists:blood_units,id'],
            'method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $crossmatch = $this->crossmatches->perform($bloodRequest, BloodUnit::findOrFail($data['blood_unit_id']), $request->user(), $data['method'] ?? null, $data['notes'] ?? null);

        return back()->with($crossmatch->result === 'COMPATIBLE' ? 'success' : 'error', 'Crossmatch result: '.$crossmatch->result);
    }
}

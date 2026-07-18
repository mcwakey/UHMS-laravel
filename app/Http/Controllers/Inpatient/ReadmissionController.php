<?php

namespace App\Http\Controllers\Inpatient;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Services\Admissions\AdmissionExtensionService;
use Illuminate\Http\Request;

class ReadmissionController extends Controller
{
    public function create(Admission $admission)
    {
        $this->assertDischarged($admission);
        $admission->loadMissing(['patient', 'visit', 'bed.ward']);

        return view('inpatient.readmissions.create', compact('admission'));
    }

    public function store(Request $request, Admission $admission, AdmissionExtensionService $extensions)
    {
        $this->assertDischarged($admission);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $admission = $extensions->extend($admission, $request->user(), $data['reason']);

        return redirect()
            ->route('inpatient.admissions.show', $admission)
            ->with('success', __('inpatient.readmission.success'));
    }

    private function assertDischarged(Admission $admission): void
    {
        abort_unless($admission->status === AdmissionStatus::DISCHARGED, 409);
    }
}

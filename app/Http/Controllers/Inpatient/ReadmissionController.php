<?php

namespace App\Http\Controllers\Inpatient;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Services\Admissions\AdmissionExtensionService;
use Illuminate\Http\Request;

class ReadmissionController extends Controller
{
    public function create(Admission $admission)
    {
        abort_unless(app(AdmissionExtensionService::class)->canExtend($admission), 409, __('admissions.extension_window_expired'));

        $admission->loadMissing(['patient', 'visit', 'bed.ward']);

        return view('inpatient.readmissions.create', compact('admission'));
    }

    public function store(Request $request, Admission $admission, AdmissionExtensionService $extensions)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $admission = $extensions->extend($admission, $request->user(), $data['reason']);

        return redirect()
            ->route('inpatient.admissions.show', $admission)
            ->with('success', __('inpatient.readmission.success'));
    }

}

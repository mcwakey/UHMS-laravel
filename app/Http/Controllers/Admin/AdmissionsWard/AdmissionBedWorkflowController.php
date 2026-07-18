<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\BedStatus;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Services\Admissions\BedWorkflowService;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdmissionBedWorkflowController extends Controller
{
    public function __construct(private BedWorkflowService $beds) {}

    public function transfer(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'bed_id' => ['required', 'exists:beds,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->beds->transferAdmission($admission, Bed::findOrFail($data['bed_id']), $request->user(), $data['reason']);

        return redirect()
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.admissions.show'), $admission)
            ->with('success', __('admissions.transfer_messages.transferred'));
    }

    public function updateBedStatus(Request $request, Bed $bed)
    {
        $data = $request->validate([
            'status' => ['required', new Enum(BedStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->beds->updateBedStatus($bed, BedStatus::from($data['status']), $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('admissions.transfer_messages.bed_status_updated'));
    }
}

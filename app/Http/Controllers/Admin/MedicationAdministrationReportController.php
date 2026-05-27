<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClinicalTaskReminderService;
use App\Services\MedicationAdministrationReportService;
use Illuminate\Http\Request;

class MedicationAdministrationReportController extends Controller
{
    public function __construct(
        private MedicationAdministrationReportService $reports,
        private ClinicalTaskReminderService $reminders,
    ) {}

    public function index(Request $request)
    {
        $this->reminders->syncMedicationTaskStatuses();

        return view('medication-administration.reports', [
            'administrations' => $this->reports->administrations($request->all()),
            'overdueTasks' => $this->reports->overdueTasks($request->all()),
            'filters' => $request->only(['date_from', 'date_to', 'status', 'nurse_id', 'patient_id']),
        ]);
    }
}

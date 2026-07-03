<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationPlanning;

class ConsultationPlanningController extends ConsultationWorkflowController
{
    use HandlesConsultationPlanning;
}

<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationWorkspace;

class ConsultationWorkspaceController extends ConsultationWorkflowController
{
    use HandlesConsultationWorkspace;
}

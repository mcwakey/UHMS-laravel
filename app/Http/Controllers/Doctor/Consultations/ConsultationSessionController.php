<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationSessions;

class ConsultationSessionController extends ConsultationWorkflowController
{
    use HandlesConsultationSessions;
}

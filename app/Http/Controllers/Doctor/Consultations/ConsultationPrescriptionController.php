<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationPrescriptions;

class ConsultationPrescriptionController extends ConsultationWorkflowController
{
    use HandlesConsultationPrescriptions;
}

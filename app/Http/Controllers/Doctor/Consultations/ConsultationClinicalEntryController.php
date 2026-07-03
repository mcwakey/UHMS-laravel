<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationClinicalEntries;

class ConsultationClinicalEntryController extends ConsultationWorkflowController
{
    use HandlesConsultationClinicalEntries;
}

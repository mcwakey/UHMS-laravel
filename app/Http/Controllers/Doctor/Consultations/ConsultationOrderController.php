<?php

namespace App\Http\Controllers\Doctor\Consultations;

use App\Http\Controllers\Doctor\Consultations\Concerns\HandlesConsultationOrders;

class ConsultationOrderController extends ConsultationWorkflowController
{
    use HandlesConsultationOrders;
}

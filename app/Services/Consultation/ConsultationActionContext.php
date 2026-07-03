<?php

namespace App\Services\Consultation;

use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;

class ConsultationActionContext
{
    public function __construct(
        public readonly Visit $visit,
        public readonly VisitConsultationRoute $route,
        public readonly MedicalRecord $medicalRecord,
        public readonly User $user,
    ) {}
}


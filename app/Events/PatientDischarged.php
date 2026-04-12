<?php

namespace App\Events;

use App\Models\Admission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientDischarged
{
    use Dispatchable, SerializesModels;

    public function __construct(public Admission $admission) {}
}

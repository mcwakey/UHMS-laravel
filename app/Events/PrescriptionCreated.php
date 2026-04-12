<?php

namespace App\Events;

use App\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Prescription $prescription) {}
}

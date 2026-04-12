<?php

namespace App\Events;

use App\Models\LabRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabResultsCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabRequest $labRequest) {}
}

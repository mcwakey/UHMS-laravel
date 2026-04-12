<?php

namespace App\Events;

use App\Models\LabRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabRequest $labRequest) {}
}

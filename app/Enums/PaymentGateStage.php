<?php

namespace App\Enums;

enum PaymentGateStage: string
{
    case START = 'start';
    case PERFORM = 'perform';
    case RESULT = 'result';
    case COMPLETE = 'complete';
    case DISPENSE = 'dispense';
    case ISSUE = 'issue';
    case RENDER = 'render';
    case READINESS = 'readiness';

    public function label(): string
    {
        return __('payment_timing.gate_stages.'.$this->value);
    }
}

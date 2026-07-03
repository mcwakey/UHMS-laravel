<?php

namespace App\Services\Consultation;

class ConsultationCompletionReadinessResult
{
    public function __construct(private readonly array $requirements) {}

    public function requirements(): array
    {
        return $this->requirements;
    }

    public function missing(): array
    {
        return array_values(array_filter($this->requirements, fn (array $item) => ! $item['met']));
    }

    public function ready(): bool
    {
        return $this->missing() === [];
    }

    public function toArray(): array
    {
        return [
            'ready' => $this->ready(),
            'requirements' => $this->requirements,
            'missing' => $this->missing(),
        ];
    }
}

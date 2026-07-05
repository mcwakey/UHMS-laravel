<?php

namespace App\Data\Consultation\Specialty;

class DoctorSpecialtyWorkspace
{
    public function __construct(
        public readonly array $doctor,
        public readonly ?array $profile,
        public readonly ?array $department,
        public readonly ?array $specialty,
        public readonly array $metrics,
        public readonly array $quickActions,
        public readonly array $pinnedActions,
        public readonly array $alerts,
        public readonly array $preferences,
        public readonly array $todayContext,
        public readonly array $summaryBuilder,
        public readonly array $readiness,
        public readonly array $orderSets,
        public readonly bool $isFallback,
    ) {}

    public function toArray(): array
    {
        return [
            'doctor' => $this->doctor,
            'profile' => $this->profile,
            'department' => $this->department,
            'specialty' => $this->specialty,
            'metrics' => $this->metrics,
            'quick_actions' => $this->quickActions,
            'pinned_actions' => $this->pinnedActions,
            'alerts' => $this->alerts,
            'preferences' => $this->preferences,
            'today_context' => $this->todayContext,
            'summary_builder' => $this->summaryBuilder,
            'readiness' => $this->readiness,
            'order_sets' => $this->orderSets,
            'is_fallback' => $this->isFallback,
        ];
    }
}

<?php

namespace App\Services\Maternity\Context;

/**
 * Phase 14R.5 — one place that reads the four integration flags.
 *
 * All default false. A handoff flag can never make a surface do MORE than its
 * context flag allows where the two are paired, and no flag here influences the
 * four independent Obstetrics/Gynaecology flags in config/consultation.php.
 */
class MaternityIntegrationFlags
{
    /** Consultation → Admission Request, Gynaecology referral, Postnatal review. */
    public function consultationHandoffsEnabled(): bool
    {
        return (bool) config('maternity.integration.consultation_handoffs_enabled', false);
    }

    /** Read-only maternity context card in the Emergency workspace. */
    public function emergencyContextEnabled(): bool
    {
        return (bool) config('maternity.integration.emergency_context_enabled', false);
    }

    /** Read-only maternity context in Admission + request→admission propagation. */
    public function admissionContextEnabled(): bool
    {
        return (bool) config('maternity.integration.admission_context_enabled', false);
    }

    /**
     * Explicit Emergency ↔ Maternity actions.
     *
     * Gated by the Emergency context flag as well: an action surface can never
     * appear where the read-only context it acts on is switched off. Enforced
     * in code, not merely by convention.
     */
    public function emergencyHandoffsEnabled(): bool
    {
        return $this->emergencyContextEnabled()
            && (bool) config('maternity.integration.emergency_handoffs_enabled', false);
    }

    /**
     * Maternity → Emergency escalation handoffs.
     *
     * Deliberately NOT gated by the Emergency context flag: the action starts in
     * the Maternity workspace, which has its own permissions, and a site may
     * want escalation without the Emergency card.
     */
    public function maternityEmergencyHandoffsEnabled(): bool
    {
        return (bool) config('maternity.integration.emergency_handoffs_enabled', false);
    }

    /** @return array<string, bool> identifier-only snapshot for reports/tests */
    public function toArray(): array
    {
        return [
            'consultation_handoffs' => $this->consultationHandoffsEnabled(),
            'emergency_context' => $this->emergencyContextEnabled(),
            'admission_context' => $this->admissionContextEnabled(),
            'emergency_handoffs' => $this->emergencyHandoffsEnabled(),
            'maternity_emergency_handoffs' => $this->maternityEmergencyHandoffsEnabled(),
        ];
    }
}

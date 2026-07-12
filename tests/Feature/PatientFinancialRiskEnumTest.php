<?php

namespace Tests\Feature;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskReason;
use App\Enums\PatientFinancialRiskStatus;
use Tests\TestCase;

class PatientFinancialRiskEnumTest extends TestCase
{
    public function test_all_required_levels_reasons_and_statuses_exist(): void
    {
        $this->assertSame(
            ['normal', 'watchlist', 'high_risk', 'blocked_credit'],
            array_map(fn ($c) => $c->value, PatientFinancialRiskLevel::cases()),
        );
        $this->assertContains('management_decision', array_map(fn ($c) => $c->value, PatientFinancialRiskReason::cases()));
        $this->assertContains('other', array_map(fn ($c) => $c->value, PatientFinancialRiskReason::cases()));
        $this->assertSame(
            ['active', 'under_review', 'suspended', 'cleared', 'expired'],
            array_map(fn ($c) => $c->value, PatientFinancialRiskStatus::cases()),
        );
    }

    public function test_labels_resolve_through_localisation(): void
    {
        $this->assertSame(__('patient_financial_risk.levels.high_risk.label'), PatientFinancialRiskLevel::HIGH_RISK->label());
        $this->assertSame(__('patient_financial_risk.reasons.other'), PatientFinancialRiskReason::OTHER->label());
        $this->assertSame(__('patient_financial_risk.statuses.suspended'), PatientFinancialRiskStatus::SUSPENDED->label());
        $this->assertNotSame('patient_financial_risk.levels.high_risk.label', PatientFinancialRiskLevel::HIGH_RISK->label());
    }

    public function test_restriction_and_terminal_semantics(): void
    {
        $this->assertFalse(PatientFinancialRiskLevel::NORMAL->isRestrictive());
        $this->assertTrue(PatientFinancialRiskLevel::BLOCKED_CREDIT->isRestrictive());

        $this->assertTrue(PatientFinancialRiskStatus::CLEARED->isTerminal());
        $this->assertTrue(PatientFinancialRiskStatus::EXPIRED->isTerminal());
        $this->assertFalse(PatientFinancialRiskStatus::ACTIVE->isTerminal());
    }

    public function test_reason_details_requirement(): void
    {
        $this->assertTrue(PatientFinancialRiskReason::OTHER->requiresDetails());
        $this->assertTrue(PatientFinancialRiskReason::MANAGEMENT_DECISION->requiresDetails());
        $this->assertFalse(PatientFinancialRiskReason::CREDIT_LIMIT_EXCEEDED->requiresDetails());
    }

    public function test_state_transition_matrix(): void
    {
        $this->assertTrue(PatientFinancialRiskStatus::ACTIVE->canTransitionTo(PatientFinancialRiskStatus::SUSPENDED));
        $this->assertTrue(PatientFinancialRiskStatus::SUSPENDED->canTransitionTo(PatientFinancialRiskStatus::ACTIVE));
        $this->assertFalse(PatientFinancialRiskStatus::CLEARED->canTransitionTo(PatientFinancialRiskStatus::ACTIVE));
        $this->assertFalse(PatientFinancialRiskStatus::EXPIRED->canTransitionTo(PatientFinancialRiskStatus::ACTIVE));
    }
}

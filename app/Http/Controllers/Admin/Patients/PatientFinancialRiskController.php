<?php

namespace App\Http\Controllers\Admin\Patients;

use App\Data\Billing\PatientFinancialRiskData;
use App\Exceptions\InvalidFinancialRiskTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ClearPatientFinancialRiskRequest;
use App\Http\Requests\Billing\ReactivatePatientFinancialRiskRequest;
use App\Http\Requests\Billing\ReviewPatientFinancialRiskRequest;
use App\Http\Requests\Billing\StorePatientFinancialRiskRequest;
use App\Http\Requests\Billing\SuspendPatientFinancialRiskRequest;
use App\Http\Requests\Billing\UpdatePatientFinancialRiskRequest;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use App\Services\Billing\PatientFinancialRiskService;
use Illuminate\Http\RedirectResponse;

/**
 * Patient-scoped financial-risk actions (Payment Timing Policy Phase 5). Every
 * endpoint is permission-guarded; no action changes any payment gate or visit.
 */
class PatientFinancialRiskController extends Controller
{
    public function __construct(private readonly PatientFinancialRiskService $service) {}

    public function store(StorePatientFinancialRiskRequest $request, Patient $patient): RedirectResponse
    {
        $this->service->createOrClassify($patient, PatientFinancialRiskData::fromValidated($request->validated()), $request->user());

        return $this->back(__('patient_financial_risk.flash.classified'));
    }

    public function update(UpdatePatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        $this->ensureBelongs($patient, $profile);
        $this->service->update($profile, PatientFinancialRiskData::fromValidated($request->validated()), $request->user());

        return $this->back(__('patient_financial_risk.flash.updated'));
    }

    public function submitForReview(ReviewPatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        return $this->transition($patient, $profile, fn () => $this->service->submitForReview($profile, $request->user(), $request->input('reason')), 'submitted_for_review');
    }

    public function completeReview(ReviewPatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        return $this->transition($patient, $profile, fn () => $this->service->completeReview($profile, $request->user(), $request->input('reason')), 'review_completed');
    }

    public function suspend(SuspendPatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        return $this->transition($patient, $profile, fn () => $this->service->suspend($profile, $request->user(), $request->input('reason')), 'suspended');
    }

    public function reactivate(ReactivatePatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        return $this->transition($patient, $profile, fn () => $this->service->reactivate($profile, $request->user(), $request->input('reason')), 'reactivated');
    }

    public function clear(ClearPatientFinancialRiskRequest $request, Patient $patient, PatientFinancialRiskProfile $profile): RedirectResponse
    {
        return $this->transition($patient, $profile, fn () => $this->service->clear($profile, $request->user(), $request->input('reason')), 'cleared');
    }

    private function transition(Patient $patient, PatientFinancialRiskProfile $profile, callable $action, string $flashKey): RedirectResponse
    {
        $this->ensureBelongs($patient, $profile);

        try {
            $action();
        } catch (InvalidFinancialRiskTransitionException $e) {
            return back()->with('error', __('patient_financial_risk.flash.invalid_transition'));
        }

        return $this->back(__("patient_financial_risk.flash.{$flashKey}"));
    }

    private function ensureBelongs(Patient $patient, PatientFinancialRiskProfile $profile): void
    {
        abort_unless($profile->patient_id === $patient->id, 404);
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.patients.show', $patient = request()->route('patient'))
            ->withFragment('financial-risk')
            ->with('success', $message);
    }
}

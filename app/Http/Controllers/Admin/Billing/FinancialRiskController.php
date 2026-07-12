<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Http\Controllers\Controller;
use App\Models\PatientFinancialRiskProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin financial-risk worklist and report (Payment Timing Policy Phase 5).
 * Read-oriented and permission-guarded. Nothing here changes payment behaviour.
 */
class FinancialRiskController extends Controller
{
    /** Worklist of patient financial-risk profiles with filters, sort and pagination. */
    public function index(Request $request)
    {
        $sortColumn = in_array($request->get('sort'), ['risk_level', 'effective_from', 'review_due_at', 'expires_at'], true)
            ? $request->get('sort')
            : 'effective_from';
        $sortDir = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        $profiles = $this->baseQuery($request)
            ->with(['patient:id,patient_number,first_name,last_name,other_names'])
            ->orderBy($sortColumn, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.billing.financial-risk.index', [
            'profiles' => $profiles,
            'levels' => PatientFinancialRiskLevel::cases(),
            'statuses' => PatientFinancialRiskStatus::cases(),
            'filters' => $request->only(['q', 'level', 'status', 'review_due', 'expired', 'restriction', 'sort', 'dir']),
        ]);
    }

    /** Aggregated read-only report for authorised users. */
    public function report(Request $request)
    {
        $restrictive = fn (PatientFinancialRiskLevel $level) => PatientFinancialRiskProfile::query()
            ->active()->forRiskLevel($level)->count();

        $metrics = [
            'active_watchlist' => $restrictive(PatientFinancialRiskLevel::WATCHLIST),
            'active_high_risk' => $restrictive(PatientFinancialRiskLevel::HIGH_RISK),
            'active_blocked_credit' => $restrictive(PatientFinancialRiskLevel::BLOCKED_CREDIT),
            'due_for_review' => PatientFinancialRiskProfile::query()->dueForReview()->count(),
            'expiring_soon' => PatientFinancialRiskProfile::query()->active()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now())
                ->whereDate('expires_at', '<=', now()->addDays(30))
                ->count(),
            'cleared_this_month' => PatientFinancialRiskProfile::query()
                ->where('status', PatientFinancialRiskStatus::CLEARED->value)
                ->whereBetween('cleared_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'created_this_month' => PatientFinancialRiskProfile::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];

        return view('admin.billing.financial-risk.report', ['metrics' => $metrics]);
    }

    /** Restricted CSV export — only necessary fields; no clinical/contact data. */
    public function export(Request $request): StreamedResponse
    {
        $profiles = $this->baseQuery($request)
            ->with(['patient:id,patient_number,first_name,last_name,other_names'])
            ->orderBy('effective_from', 'desc')
            ->limit(5000)
            ->get();

        $filename = 'financial-risk-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($profiles): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                __('patient_financial_risk.export.patient_number'),
                __('patient_financial_risk.export.patient_name'),
                __('patient_financial_risk.fields.risk_level'),
                __('patient_financial_risk.fields.status'),
                __('patient_financial_risk.fields.primary_reason'),
                __('patient_financial_risk.fields.effective_from'),
                __('patient_financial_risk.fields.review_due_at'),
                __('patient_financial_risk.fields.expires_at'),
                __('patient_financial_risk.fields.credit_limit'),
            ]);
            foreach ($profiles as $profile) {
                fputcsv($out, [
                    $profile->patient?->patient_number,
                    $profile->patient?->full_name,
                    $profile->risk_level->label(),
                    $profile->status->label(),
                    $profile->primary_reason?->label(),
                    optional($profile->effective_from)->toDateString(),
                    optional($profile->review_due_at)->toDateString(),
                    optional($profile->expires_at)->toDateString(),
                    $profile->credit_limit,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function baseQuery(Request $request)
    {
        $query = PatientFinancialRiskProfile::query();

        if ($level = $request->get('level')) {
            $query->where('risk_level', $level);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->boolean('review_due')) {
            $query->dueForReview();
        }
        if ($request->boolean('expired')) {
            $query->where('status', PatientFinancialRiskStatus::EXPIRED->value);
        }
        if ($request->boolean('restriction')) {
            $query->restrictive();
        }
        if ($term = trim((string) $request->get('q'))) {
            $query->whereHas('patient', fn ($p) => $p->search($term));
        }

        return $query;
    }
}

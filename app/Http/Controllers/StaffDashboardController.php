<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Appointment;
use App\Models\StockBalance;
use App\Models\DrugStock;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    /**
     * Single, role-aware staff landing page.
     * Each role gets a focused panel of *their* work for the day —
     * no full admin overview, no doctor EHR, just what they need.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = $user->getRoleNames()->first() ?? 'Staff';

        $stats = [];
        $lists = [];

        if ($user->hasRole('Receptionist')) {
            $stats['todayVisits']       = Visit::today()->count();
            $stats['todayAppointments'] = Appointment::today()->count();
            $stats['waitingQueue']      = Visit::today()->where('status', 'waiting')->count();
            $lists['recentVisits']      = Visit::with(['patient', 'department'])->today()->latest()->take(8)->get();
        }

        if ($user->hasRole('Nurse')) {
            $stats['triageQueue']  = Visit::today()->where('status', 'triage')->count();
            $stats['inConsultation'] = Visit::today()->where('status', 'in_consultation')->count();
            $lists['triagePatients'] = Visit::with(['patient', 'department'])
                ->today()->where('status', 'triage')->latest()->take(10)->get();
        }

        if ($user->hasRole('Pharmacist')) {
            $stats['pendingRx']  = Prescription::where('status', 'pending')->count();
            // Low stock from new stock_balances + drugs.reorder_level (SoT).
            $stats['lowStock']   = StockBalance::query()
                ->where('quantity_on_hand', '>', 0)
                ->whereColumn('quantity_on_hand', '<=', DB::raw('COALESCE((SELECT reorder_level FROM drugs WHERE drugs.id = stock_balances.drug_id), 0)'))
                ->count();
            // Expired stock still tracked at batch level via DrugStock (internal).
            $stats['expired']    = DrugStock::whereDate('expiry_date', '<', today())->where('quantity', '>', 0)->count();
            $lists['recentRx']   = Prescription::with(['patient'])->latest()->take(8)->get();
        }

        if ($user->hasRole('Lab Technician')) {
            $stats['pendingLab']   = LabRequest::where('status', 'pending')->count();
            $stats['inProgressLab'] = LabRequest::where('status', 'in_progress')->count();
            $stats['completedToday'] = LabRequest::whereDate('updated_at', today())->where('status', 'completed')->count();
            $lists['pendingRequests'] = LabRequest::with(['visit.patient'])->where('status', 'pending')->latest()->take(10)->get();
        }

        if ($user->hasAnyRole(['Accountant', 'Cashier'])) {
            $stats['unpaidInvoices'] = Invoice::where('status', 'unpaid')->count();
            $stats['todayInvoices']  = Invoice::whereDate('created_at', today())->count();
            $lists['recentInvoices'] = Invoice::with(['patient'])->latest()->take(10)->get();
        }

        return view('dashboard.staff', compact('user', 'role', 'stats', 'lists'));
    }
}

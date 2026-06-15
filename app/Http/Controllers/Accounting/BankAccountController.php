<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Services\BankAccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function __construct(protected BankAccountService $service) {}

    public function index(Request $request)
    {
        $bankAccounts = BankAccount::with('glAccount')
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.bank.accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        return view('accounting.bank.accounts.create', [
            'glAccounts' => $this->glAccounts(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $this->service->create($data, $request->user());

        return redirect()->route('admin.accounting.bank.accounts.index')
            ->with('success', __('messages.accounting.bank_account_created'));
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('accounting.bank.accounts.edit', [
            'bankAccount' => $bankAccount,
            'glAccounts' => $this->glAccounts(),
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $this->validateData($request, $bankAccount);
        $this->service->update($bankAccount, $data, $request->user());

        return redirect()->route('admin.accounting.bank.accounts.index')
            ->with('success', __('messages.accounting.bank_account_updated'));
    }

    public function disable(Request $request, BankAccount $bankAccount)
    {
        $this->service->disable($bankAccount, $request->user());

        return back()->with('success', __('messages.accounting.bank_account_disabled'));
    }

    public function activate(Request $request, BankAccount $bankAccount)
    {
        $this->service->activate($bankAccount, $request->user());

        return back()->with('success', __('messages.accounting.bank_account_updated'));
    }

    protected function validateData(Request $request, ?BankAccount $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'bank_name' => ['required', 'string', 'max:160'],
            'branch_name' => ['nullable', 'string', 'max:160'],
            'account_name' => ['nullable', 'string', 'max:160'],
            'account_number' => [$existing ? 'nullable' : 'required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3'],
            'gl_account_id' => ['required', Rule::exists('accounts', 'id')],
            'opening_date' => ['nullable', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function glAccounts()
    {
        return Account::query()
            ->active()
            ->where(fn ($q) => $q->where('is_bank_account', true)->orWhere('is_cash_account', true))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }
}

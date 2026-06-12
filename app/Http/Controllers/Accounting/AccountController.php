<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\NormalBalance;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\ChartOfAccountsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::with('parent')
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->byType($request->input('type'))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', (bool) $request->boolean('active')))
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.accounts.index', [
            'accounts' => $accounts,
            'types' => AccountType::cases(),
        ]);
    }

    public function create()
    {
        return view('accounting.accounts.create', $this->formData());
    }

    public function store(Request $request, ChartOfAccountsService $service)
    {
        $data = $this->validated($request);
        $account = $service->create($data, $request->user());

        return redirect()
            ->route('admin.accounting.accounts.edit', $account)
            ->with('success', __('messages.accounting.account_created'));
    }

    public function edit(Account $account)
    {
        return view('accounting.accounts.edit', array_merge($this->formData($account), compact('account')));
    }

    public function update(Request $request, Account $account, ChartOfAccountsService $service)
    {
        $data = $this->validated($request, $account);
        $service->update($account, $data, $request->user());

        return redirect()
            ->route('admin.accounting.accounts.edit', $account)
            ->with('success', __('messages.accounting.account_updated'));
    }

    public function disable(Request $request, Account $account, ChartOfAccountsService $service)
    {
        $service->disable($account, $request->user());

        return back()->with('success', __('messages.accounting.account_disabled'));
    }

    public function activate(Request $request, Account $account, ChartOfAccountsService $service)
    {
        $service->activate($account, $request->user());

        return back()->with('success', __('messages.accounting.account_reactivated'));
    }

    protected function formData(?Account $account = null): array
    {
        return [
            'types' => AccountType::cases(),
            'normalBalances' => NormalBalance::cases(),
            'parentAccounts' => Account::query()
                ->when($account, fn ($q) => $q->whereKeyNot($account->id))
                ->orderBy('code')
                ->get(),
        ];
    }

    protected function validated(Request $request, ?Account $account = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->ignore($account)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_map(fn (AccountType $type) => $type->value, AccountType::cases()))],
            'subtype' => ['nullable', 'string', 'max:60'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'description' => ['nullable', 'string'],
            'normal_balance' => ['nullable', Rule::in(array_map(fn (NormalBalance $balance) => $balance->value, NormalBalance::cases()))],
            'opening_balance' => ['nullable', 'numeric'],
            'is_cash_account' => ['nullable', 'boolean'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_control_account' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}

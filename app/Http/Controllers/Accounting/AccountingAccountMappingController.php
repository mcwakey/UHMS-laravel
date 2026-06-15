<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingAccountMapping;
use App\Models\Department;
use App\Services\AccountingAccountMappingService;
use Illuminate\Http\Request;

class AccountingAccountMappingController extends Controller
{
    public function index(Request $request)
    {
        $mappings = AccountingAccountMapping::query()
            ->with(['account', 'department'])
            ->when($request->filled('scope'), fn ($q) => $q->where('mapping_scope', (string) $request->string('scope')))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('mapping_scope')
            ->orderBy('mapping_key')
            ->orderByDesc('priority')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.mappings.index', [
            'mappings' => $mappings,
            'scopes' => AccountingAccountMapping::query()->distinct()->orderBy('mapping_scope')->pluck('mapping_scope'),
        ]);
    }

    public function create()
    {
        return view('accounting.mappings.create', $this->formData());
    }

    public function store(Request $request, AccountingAccountMappingService $service)
    {
        $mapping = $service->create($this->validated($request), $request->user());

        return redirect()->route('admin.accounting.mappings.edit', $mapping)
            ->with('success', __('accounting.mapping_created'));
    }

    public function edit(AccountingAccountMapping $mapping)
    {
        return view('accounting.mappings.edit', array_merge($this->formData(), compact('mapping')));
    }

    public function update(
        Request $request,
        AccountingAccountMapping $mapping,
        AccountingAccountMappingService $service,
    ) {
        $service->update($mapping, $this->validated($request), $request->user());

        return back()->with('success', __('accounting.mapping_updated'));
    }

    public function disable(
        Request $request,
        AccountingAccountMapping $mapping,
        AccountingAccountMappingService $service,
    ) {
        $service->disable($mapping, $request->user());

        return back()->with('success', __('accounting.mapping_disabled'));
    }

    protected function formData(): array
    {
        return [
            'accounts' => Account::active()->orderBy('code')->get(),
            'departments' => Department::orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'mapping_scope' => ['required', 'string', 'max:50'],
            'mapping_key' => ['required', 'string', 'max:60'],
            'mapping_value' => ['required', 'string', 'max:80'],
            'account_id' => ['required', 'exists:accounts,id'],
            'facility_id' => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'priority' => ['required', 'integer', 'between:-100000,100000'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}

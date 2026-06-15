<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingPostingTemplate;
use App\Services\PostingTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountingPostingTemplateController extends Controller
{
    public function __construct(protected PostingTemplateService $templates) {}

    public function index()
    {
        return view('accounting.posting-templates.index', [
            'templates' => AccountingPostingTemplate::withCount('lines')->orderBy('entry_type')->orderBy('code')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('accounting.posting-templates.form', $this->formData(new AccountingPostingTemplate));
    }

    public function store(Request $request)
    {
        $template = $this->templates->save($this->validated($request), null, $request->user());
        return redirect()->route('admin.accounting.posting-templates.edit', $template)->with('success', __('accounting.template_saved'));
    }

    public function edit(AccountingPostingTemplate $postingTemplate)
    {
        return view('accounting.posting-templates.form', $this->formData($postingTemplate->load('lines')));
    }

    public function update(Request $request, AccountingPostingTemplate $postingTemplate)
    {
        $this->templates->save($this->validated($request, $postingTemplate), $postingTemplate, $request->user());
        return back()->with('success', __('accounting.template_saved'));
    }

    public function approve(Request $request, AccountingPostingTemplate $postingTemplate)
    {
        $this->templates->approve($postingTemplate, $request->user());
        return back()->with('success', __('accounting.template_approved'));
    }

    public function disable(Request $request, AccountingPostingTemplate $postingTemplate)
    {
        $this->templates->disable($postingTemplate, $request->user());
        return back()->with('success', __('accounting.template_disabled'));
    }

    protected function validated(Request $request, ?AccountingPostingTemplate $template = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('accounting_posting_templates')->ignore($template)],
            'name' => ['required', 'string', 'max:160'],
            'entry_type' => ['required', Rule::in(['income', 'expense'])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.side' => ['required', Rule::in(['debit', 'credit'])],
            'lines.*.account_source_type' => ['required', Rule::in(['mapping', 'fixed'])],
            'lines.*.fixed_account_id' => ['nullable', 'exists:accounts,id'],
            'lines.*.mapping_scope' => ['nullable', 'string', 'max:50'],
            'lines.*.mapping_key_source' => ['nullable', 'string', 'max:60'],
            'lines.*.mapping_value_source' => ['nullable', 'string', 'max:80'],
            'lines.*.description_template' => ['nullable', 'string', 'max:255'],
            'lines.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    protected function formData(AccountingPostingTemplate $template): array
    {
        return [
            'template' => $template,
            'accounts' => Account::active()->orderBy('code')->get(),
            'scopes' => ['basic_income_category', 'basic_expense_category', 'basic_payment_method', 'basic_cash_account', 'basic_bank_account'],
        ];
    }
}

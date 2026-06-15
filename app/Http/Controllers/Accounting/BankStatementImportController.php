<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Services\BankStatementImportService;
use Illuminate\Http\Request;

class BankStatementImportController extends Controller
{
    public function __construct(protected BankStatementImportService $service) {}

    public function index(Request $request)
    {
        $imports = BankStatementImport::with(['bankAccount', 'importedBy'])
            ->when($request->filled('bank_account_id'), fn ($q) => $q->where('bank_account_id', $request->bank_account_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('accounting.bank.imports.index', [
            'imports' => $imports,
            'bankAccounts' => BankAccount::active()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('accounting.bank.imports.create', [
            'bankAccounts' => BankAccount::active()->orderBy('name')->get(),
            'selectedBankAccountId' => $request->integer('bank_account_id') ?: null,
        ]);
    }

    /**
     * Parse + validate the uploaded file and show a preview. Writes no lines.
     */
    public function preview(Request $request)
    {
        $data = $this->validateUpload($request);
        $account = BankAccount::findOrFail($data['bank_account_id']);

        $preview = $this->service->preview(
            $account,
            file_get_contents($request->file('file')->getRealPath()),
            $this->mapping($request),
            $request->user(),
        );

        return view('accounting.bank.imports.preview', [
            'bankAccount' => $account,
            'preview' => $preview,
            'mapping' => $this->mapping($request),
            'meta' => $this->meta($request),
            'fileContents' => base64_encode(file_get_contents($request->file('file')->getRealPath())),
        ]);
    }

    /**
     * Confirm the import from the preview screen.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'file_contents' => ['required', 'string'],
        ]);

        $account = BankAccount::findOrFail($request->bank_account_id);
        $contents = base64_decode($request->input('file_contents'));

        $import = $this->service->import(
            $account,
            $contents,
            (array) $request->input('mapping', []),
            $this->meta($request) + ['mapping' => (array) $request->input('mapping', [])],
            $request->user(),
        );

        return redirect()->route('admin.accounting.bank.imports.show', $import)
            ->with('success', __('messages.accounting.statement_imported', ['count' => $import->line_count]));
    }

    public function show(BankStatementImport $import)
    {
        $import->load(['bankAccount', 'importedBy', 'rejectedBy', 'lines' => fn ($q) => $q->orderBy('line_number')]);

        return view('accounting.bank.imports.show', compact('import'));
    }

    public function reject(Request $request, BankStatementImport $import)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->service->reject($import, $data['reason'], $request->user());

        return back()->with('success', __('messages.accounting.statement_rejected'));
    }

    protected function validateUpload(Request $request): array
    {
        return $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
        ]);
    }

    protected function mapping(Request $request): array
    {
        $columns = (array) $request->input('columns', [
            'transaction_date' => 'date',
            'value_date' => 'value_date',
            'reference' => 'reference',
            'description' => 'description',
            'debit' => 'debit',
            'credit' => 'credit',
            'balance_after' => 'balance',
        ]);

        return [
            'columns' => $columns,
            'date_format' => $request->input('date_format', 'Y-m-d'),
            'has_header' => $request->boolean('has_header', true),
            'amount_sign' => $request->input('amount_sign', 'credit_positive'),
        ];
    }

    protected function meta(Request $request): array
    {
        return [
            'original_filename' => $request->file('file')?->getClientOriginalName() ?? $request->input('original_filename'),
            'period_start' => $request->input('period_start'),
            'period_end' => $request->input('period_end'),
            'opening_balance' => $request->input('opening_balance'),
            'closing_balance' => $request->input('closing_balance'),
        ];
    }
}

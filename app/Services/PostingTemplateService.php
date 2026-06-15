<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\AccountingPostingTemplate;
use App\Models\AccountingPostingTemplateLine;
use App\Models\FinancialEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostingTemplateService
{
    public function __construct(
        protected AccountingAccountMappingService $mappings,
        protected ActivityLogService $activityLog,
    ) {}

    public function resolve(FinancialEntry $entry): AccountingPostingTemplate
    {
        $type = $this->entryType($entry);
        $postingType = "basic_{$type}";
        $templates = AccountingPostingTemplate::query()
            ->with('lines.fixedAccount')
            ->where('source_module', 'BASIC_ACCOUNTING')
            ->where('entry_type', $type)
            ->where('posting_type', $postingType)
            ->activeOn($entry->entry_date->toDateString())
            ->get();

        if ($templates->isEmpty()) {
            throw ValidationException::withMessages([
                'template' => "No active posting template exists for {$postingType} on {$entry->entry_date->toDateString()}.",
            ]);
        }
        if ($templates->count() > 1) {
            throw ValidationException::withMessages([
                'template' => "Multiple active posting templates match {$postingType}; disable the overlap before posting.",
            ]);
        }

        return $templates->first();
    }

    public function preview(FinancialEntry $entry): array
    {
        $template = $this->resolve($entry);
        $resolved = $template->lines
            ->where('is_active', true)
            ->map(fn (AccountingPostingTemplateLine $line) => $this->resolveLine($entry, $line))
            ->values();

        if ($resolved->count() < 2) {
            throw ValidationException::withMessages(['template' => 'The posting template must have at least two active lines.']);
        }

        $debits = round((float) $resolved->sum('debit'), 2);
        $credits = round((float) $resolved->sum('credit'), 2);
        if (abs($debits - $credits) >= 0.005) {
            throw ValidationException::withMessages(['template' => 'The resolved posting template is not balanced.']);
        }

        return [
            'template' => $template,
            'lines' => $resolved->map(fn (array $row) => $row['journal_line'])->all(),
            'snapshot' => [
                'template_id' => $template->id,
                'template_code' => $template->code,
                'template_name' => $template->name,
                'entry_type' => $this->entryType($entry),
                'resolved_lines' => $resolved->all(),
                'total_debit' => $debits,
                'total_credit' => $credits,
            ],
        ];
    }

    public function save(array $data, ?AccountingPostingTemplate $template, User $actor): AccountingPostingTemplate
    {
        return DB::transaction(function () use ($data, $template, $actor) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $template ??= new AccountingPostingTemplate;
            $old = $template->exists ? $template->load('lines')->toArray() : [];
            $template->fill($data);
            $template->source_module = 'BASIC_ACCOUNTING';
            $template->posting_type = 'basic_'.$template->entry_type;
            $template->status = 'draft';
            $template->approved_by = null;
            $template->approved_at = null;
            $template->created_by ??= $actor->id;
            $template->updated_by = $actor->id;
            $template->save();

            $template->lines()->delete();
            foreach (array_values($lines) as $index => $line) {
                $template->lines()->create(array_merge($line, [
                    'line_order' => $index + 1,
                    'fixed_account_id' => $line['fixed_account_id'] ?: null,
                    'mapping_scope' => $line['mapping_scope'] ?: null,
                    'mapping_key_source' => $line['mapping_key_source'] ?: null,
                    'mapping_value_source' => $line['mapping_value_source'] ?: null,
                    'amount_source' => 'entry_amount',
                    'is_active' => (bool) ($line['is_active'] ?? true),
                ]));
            }

            $this->audit($template, $old ? 'ACCOUNTING_POSTING_TEMPLATE_UPDATED' : 'ACCOUNTING_POSTING_TEMPLATE_CREATED', $actor, $old);
            return $template->fresh('lines');
        });
    }

    public function approve(AccountingPostingTemplate $template, User $actor): AccountingPostingTemplate
    {
        $overlap = AccountingPostingTemplate::query()
            ->where('id', '!=', $template->id)
            ->where('source_module', $template->source_module)
            ->where('entry_type', $template->entry_type)
            ->where('posting_type', $template->posting_type)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $template->effective_to ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $template->effective_from))
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages([
                'template' => 'An active template already overlaps this source, entry type, posting type and effective period.',
            ]);
        }
        $old = $template->getAttributes();
        $template->update(['status' => 'active', 'approved_by' => $actor->id, 'approved_at' => now(), 'updated_by' => $actor->id]);
        $this->audit($template, 'ACCOUNTING_POSTING_TEMPLATE_APPROVED', $actor, $old);
        return $template->refresh();
    }

    public function disable(AccountingPostingTemplate $template, User $actor): AccountingPostingTemplate
    {
        $old = $template->getAttributes();
        $template->update(['status' => 'disabled', 'updated_by' => $actor->id]);
        $this->audit($template, 'ACCOUNTING_POSTING_TEMPLATE_DISABLED', $actor, $old);
        return $template->refresh();
    }

    protected function resolveLine(FinancialEntry $entry, AccountingPostingTemplateLine $line): array
    {
        if ($line->amount_source !== 'entry_amount') {
            throw ValidationException::withMessages(['template' => "Unsupported amount source: {$line->amount_source}."]);
        }

        if ($line->account_source_type === 'fixed') {
            $account = $line->fixedAccount;
            if (! $account || ! $account->is_active) {
                throw ValidationException::withMessages(['template' => "Template line {$line->line_order} has no active fixed account."]);
            }
            $explanation = "Fixed account {$account->display_name}.";
            $mappingId = null;
        } elseif ($line->account_source_type === 'mapping') {
            $key = $this->sourceValue($entry, $line->mapping_key_source);
            $value = $this->sourceValue($entry, $line->mapping_value_source);
            $match = $this->mappings->resolve(
                (string) $line->mapping_scope,
                $key,
                $value,
                $entry->entry_date,
            );
            $account = $match['account'];
            $explanation = $match['explanation'];
            $mappingId = $match['mapping']->id;
        } else {
            throw ValidationException::withMessages(['template' => "Unsupported account source: {$line->account_source_type}."]);
        }

        $amount = round((float) $entry->amount, 2);
        $description = strtr($line->description_template ?: '{entry_number}: {description}', [
            '{entry_number}' => $entry->entry_number,
            '{description}' => $entry->description,
            '{category}' => $entry->category?->name ?? '',
        ]);
        $journalLine = [
            'account_id' => $account->id,
            'description' => $description,
            'debit' => $line->side === 'debit' ? $amount : 0,
            'credit' => $line->side === 'credit' ? $amount : 0,
            'reference_type' => FinancialEntry::class,
            'reference_id' => $entry->id,
        ];

        return [
            'line_order' => $line->line_order,
            'side' => $line->side,
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'mapping_id' => $mappingId,
            'mapping_explanation' => $explanation,
            'debit' => $journalLine['debit'],
            'credit' => $journalLine['credit'],
            'journal_line' => $journalLine,
        ];
    }

    protected function sourceValue(FinancialEntry $entry, ?string $source): string
    {
        if (! $source) {
            throw ValidationException::withMessages(['template' => 'A mapping source is missing from a template line.']);
        }
        if (str_starts_with($source, 'literal:')) {
            return substr($source, 8);
        }

        $value = match ($source) {
            'category_id' => $entry->category_id,
            'payment_method' => $entry->payment_method?->value ?? $entry->getRawOriginal('payment_method'),
            'type' => $this->entryType($entry),
            default => null,
        };
        if ($value === null || $value === '') {
            throw ValidationException::withMessages(['template' => "The source value {$source} is missing on entry {$entry->entry_number}."]);
        }
        return strtolower(trim((string) $value));
    }

    protected function entryType(FinancialEntry $entry): string
    {
        return $entry->type instanceof \BackedEnum ? $entry->type->value : (string) $entry->type;
    }

    protected function audit(AccountingPostingTemplate $template, string $event, User $actor, array $old = []): void
    {
        $this->activityLog->log(LogModule::ACCOUNTING, $event, [
            'severity' => LogSeverity::NOTICE,
            'causer' => $actor,
            'old_values' => $old,
            'new_values' => $template->load('lines')->toArray(),
            'metadata' => ['accounting_posting_template_id' => $template->id],
        ], $template, str_replace('_', ' ', ucfirst(strtolower($event))));
    }
}

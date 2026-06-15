<?php

namespace Database\Seeders;

use App\Models\AccountingPostingTemplate;
use Illuminate\Database\Seeder;

class AccountingPostingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'code' => 'basic_income',
                'name' => 'Basic income posting',
                'entry_type' => 'income',
                'posting_type' => 'basic_income',
                'description' => 'Debit the mapped payment account and credit the mapped income category account.',
                'lines' => [
                    ['line_order' => 1, 'side' => 'debit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_payment_method', 'mapping_key_source' => 'literal:method', 'mapping_value_source' => 'payment_method'],
                    ['line_order' => 2, 'side' => 'credit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_income_category', 'mapping_key_source' => 'literal:category_id', 'mapping_value_source' => 'category_id'],
                ],
            ],
            [
                'code' => 'basic_expense',
                'name' => 'Basic expense posting',
                'entry_type' => 'expense',
                'posting_type' => 'basic_expense',
                'description' => 'Debit the mapped expense category account and credit the mapped payment account.',
                'lines' => [
                    ['line_order' => 1, 'side' => 'debit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_expense_category', 'mapping_key_source' => 'literal:category_id', 'mapping_value_source' => 'category_id'],
                    ['line_order' => 2, 'side' => 'credit', 'account_source_type' => 'mapping', 'mapping_scope' => 'basic_payment_method', 'mapping_key_source' => 'literal:method', 'mapping_value_source' => 'payment_method'],
                ],
            ],
        ] as $definition) {
            $lines = $definition['lines'];
            unset($definition['lines']);

            $template = AccountingPostingTemplate::updateOrCreate(
                ['code' => $definition['code']],
                array_merge($definition, [
                    'source_module' => 'BASIC_ACCOUNTING',
                    'effective_from' => '2000-01-01',
                    'effective_to' => null,
                    'status' => 'active',
                ]),
            );

            foreach ($lines as $line) {
                $template->lines()->updateOrCreate(
                    ['line_order' => $line['line_order']],
                    array_merge($line, [
                        'amount_source' => 'entry_amount',
                        'description_template' => '{entry_number}: {description}',
                        'is_active' => true,
                    ]),
                );
            }
        }
    }
}

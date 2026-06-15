<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPostingTemplateLine extends Model
{
    protected $fillable = [
        'template_id',
        'line_order',
        'side',
        'account_source_type',
        'fixed_account_id',
        'mapping_scope',
        'mapping_key_source',
        'mapping_value_source',
        'amount_source',
        'description_template',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AccountingPostingTemplate::class, 'template_id');
    }

    public function fixedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'fixed_account_id');
    }
}

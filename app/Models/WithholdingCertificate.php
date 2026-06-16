<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingCertificate extends Model
{
    protected $fillable = ['tax_ledger_entry_id', 'certificate_number', 'certificate_date', 'counterparty_name', 'withheld_amount', 'notes'];

    protected function casts(): array
    {
        return ['certificate_date' => 'date', 'withheld_amount' => 'decimal:2'];
    }
}

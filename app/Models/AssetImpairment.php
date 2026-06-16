<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetImpairment extends Model
{
    protected $fillable = ['fixed_asset_id', 'impairment_date', 'amount', 'reason', 'journal_entry_id', 'created_by'];

    protected function casts(): array
    {
        return ['impairment_date' => 'date', 'amount' => 'decimal:2'];
    }
}

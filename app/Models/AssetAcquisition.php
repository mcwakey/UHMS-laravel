<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAcquisition extends Model
{
    protected $fillable = ['fixed_asset_id', 'source_type', 'source_id', 'source_reference', 'amount', 'journal_entry_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}

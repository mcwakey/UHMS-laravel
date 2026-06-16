<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTransfer extends Model
{
    protected $fillable = [
        'fixed_asset_id', 'from_location_id', 'to_location_id',
        'from_custodian_id', 'to_custodian_id', 'transfer_date',
        'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return ['transfer_date' => 'date'];
    }
}

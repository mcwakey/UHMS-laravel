<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCustodyAssignment extends Model
{
    protected $fillable = ['fixed_asset_id', 'custodian_id', 'asset_location_id', 'assigned_at', 'released_at', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['assigned_at' => 'date', 'released_at' => 'date'];
    }
}

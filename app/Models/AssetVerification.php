<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetVerification extends Model
{
    protected $fillable = ['fixed_asset_id', 'verification_date', 'condition_status', 'notes', 'verified_by'];

    protected function casts(): array
    {
        return ['verification_date' => 'date'];
    }
}

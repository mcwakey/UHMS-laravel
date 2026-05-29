<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogRetentionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'retention_days',
        'updated_by',
        'reason',
    ];

    protected $casts = [
        'retention_days' => 'integer',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

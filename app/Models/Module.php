<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_core',
        'is_enabled',
        'depends_on',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_core'    => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_core', false);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaternityServiceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'mapping_key',
        'service_id',
        'is_active',
        'description',
        'configured_by',
        'configured_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configured_at' => 'datetime',
        ];
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function configuredBy()
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}

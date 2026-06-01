<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintCatalogue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'body_system',
        'description',
        'keywords',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'complaint_catalogue_id');
    }
}
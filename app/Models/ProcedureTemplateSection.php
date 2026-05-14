<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureTemplateSection extends Model
{
    protected $fillable = [
        'service_id',
        'template_type',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function fields()
    {
        return $this->hasMany(ProcedureTemplateField::class, 'section_id')->orderBy('sort_order');
    }
}

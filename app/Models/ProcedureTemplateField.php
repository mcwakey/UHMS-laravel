<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureTemplateField extends Model
{
    protected $fillable = [
        'service_id',
        'section_id',
        'template_type',
        'label',
        'field_key',
        'input_type',
        'options',
        'default_value',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options'     => 'array',
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function section()
    {
        return $this->belongsTo(ProcedureTemplateSection::class, 'section_id');
    }
}

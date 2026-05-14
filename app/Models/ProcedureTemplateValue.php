<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureTemplateValue extends Model
{
    protected $fillable = [
        'procedure_request_id',
        'service_id',
        'template_field_id',
        'template_type',
        'field_key',
        'field_label',
        'input_type',
        'value',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function procedureRequest()
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function field()
    {
        return $this->belongsTo(ProcedureTemplateField::class, 'template_field_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

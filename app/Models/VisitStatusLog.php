<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

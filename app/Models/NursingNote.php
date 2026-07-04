<?php

namespace App\Models;

use App\Enums\NursingNoteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NursingNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'visit_id',
        'nurse_id',
        'note_type',
        'note',
        'observed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'note_type' => NursingNoteType::class,
            'observed_at' => 'datetime',
        ];
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'nursing_note',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CorporateClient extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'credit_limit',
        'default_due_days',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'default_due_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'is_active', 'credit_limit'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function receivables()
    {
        return $this->hasMany(InvoiceReceivable::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

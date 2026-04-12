<?php

namespace App\Models;

use App\Enums\EntryType;
use App\Enums\PaymentMethod;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FinancialEntry extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    protected $fillable = [
        'entry_number',
        'category_id',
        'type',
        'amount',
        'payment_method',
        'reference_number',
        'receipt_number',
        'description',
        'entry_date',
        'recorded_by',
        'approved_by',
    ];

    protected $casts = [
        'type' => EntryType::class,
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'entry_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    // ── Number Generation ──

    public static function generateEntryNumber(): string
    {
        return (new static)->generateNumber('FIN', 'entry_number');
    }

    // ── Relationships ──

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class, 'category_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ──

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->where('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('entry_date', '<=', $to));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('entry_number', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhere('receipt_number', 'like', "%{$term}%");
        });
    }

    // ── Accessors ──

    public function getIsApprovedAttribute(): bool
    {
        return $this->approved_by !== null;
    }
}

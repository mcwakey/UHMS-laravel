<?php

namespace App\Models;

use App\Enums\VisitFinancialClearanceBasis;
use App\Enums\VisitFinancialClearanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitFinancialClearance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => VisitFinancialClearanceStatus::class, 'basis' => VisitFinancialClearanceBasis::class,
            'requires_finance_action' => 'boolean', 'assessed_at' => 'datetime', 'cleared_at' => 'datetime',
            'conditionally_cleared_at' => 'datetime', 'financially_closed_at' => 'datetime', 'stale_at' => 'datetime',
            'reopened_at' => 'datetime', 'last_refreshed_at' => 'datetime',
        ];
    }

    public function visit() { return $this->belongsTo(Visit::class); }
    public function currentException() { return $this->belongsTo(VisitFinancialClearanceException::class, 'current_exception_id'); }
    public function history() { return $this->hasMany(VisitFinancialClearanceHistory::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function refresher() { return $this->belongsTo(User::class, 'last_refreshed_by'); }
    public function financialCloser() { return $this->belongsTo(User::class, 'financially_closed_by'); }
    public function scopePending(Builder $q) { return $q->where('status', VisitFinancialClearanceStatus::PENDING->value); }
    public function scopeCleared(Builder $q) { return $q->where('status', VisitFinancialClearanceStatus::CLEARED->value); }
    public function scopeConditionallyCleared(Builder $q) { return $q->where('status', VisitFinancialClearanceStatus::CONDITIONALLY_CLEARED->value); }
    public function scopeFinanciallyClosed(Builder $q) { return $q->where('status', VisitFinancialClearanceStatus::FINANCIALLY_CLOSED->value); }
    public function scopeStale(Builder $q) { return $q->where('status', VisitFinancialClearanceStatus::STALE->value); }
    public function scopeRequiringFinanceAction(Builder $q) { return $q->where('requires_finance_action', true); }
    public function scopeAssessedBetween(Builder $q, mixed $from, mixed $to) { return $q->whereBetween('assessed_at', [$from, $to]); }
}

<?php

namespace App\Models;

use App\Enums\VisitFinancialClearanceExceptionStatus;
use App\Enums\VisitFinancialClearanceExceptionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitFinancialClearanceException extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'type' => VisitFinancialClearanceExceptionType::class, 'status' => VisitFinancialClearanceExceptionStatus::class,
            'requested_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'financial_summary_snapshot' => 'array',
            'requested_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime',
            'effective_from' => 'date', 'expires_at' => 'date', 'withdrawn_at' => 'datetime', 'revoked_at' => 'datetime',
        ];
    }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function clearance() { return $this->belongsTo(VisitFinancialClearance::class, 'visit_financial_clearance_id'); }
    public function history() { return $this->hasMany(VisitFinancialClearanceExceptionHistory::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function scopePending(Builder $q) { return $q->where('status', VisitFinancialClearanceExceptionStatus::PENDING->value); }
    public function scopeApproved(Builder $q) { return $q->where('status', VisitFinancialClearanceExceptionStatus::APPROVED->value); }
    public function scopeCurrent(Builder $q) { return $q->approved()->where(fn ($x) => $x->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))->where(fn ($x) => $x->whereNull('expires_at')->orWhereDate('expires_at', '>=', today())); }
}

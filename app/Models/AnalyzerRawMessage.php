<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyzerRawMessage extends Model
{
    protected $fillable = [
        'analyzer_id',
        'protocol',
        'direction',
        'content',
        'content_hash',
        'processing_status',
        'processing_attempts',
        'error_message',
        'sample_id',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'processing_attempts' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function analyzer(): BelongsTo
    {
        return $this->belongsTo(Analyzer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUnprocessed($query)
    {
        return $query->whereIn('processing_status', ['received', 'failed'])
            ->where('processing_attempts', '<', 3);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('processing_status', $status);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('received_at', '>=', now()->subHours($hours));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusColorAttribute(): string
    {
        return match ($this->processing_status) {
            'received' => 'info',
            'processing' => 'warning',
            'processed' => 'success',
            'failed' => 'danger',
            'duplicate' => 'secondary',
            default => 'dark',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->processing_status));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Mark this message as a duplicate.
     */
    public function markDuplicate(): void
    {
        $this->update(['processing_status' => 'duplicate']);
    }

    /**
     * Mark processing started.
     */
    public function markProcessing(): void
    {
        $this->update([
            'processing_status' => 'processing',
            'processing_attempts' => $this->processing_attempts + 1,
        ]);
    }

    /**
     * Mark as successfully processed.
     */
    public function markProcessed(): void
    {
        $this->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed with error message.
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'error_message' => $error,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationProviderChecklistItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';
    public const STATUS_WAIVED = 'waived';

    /** A required item satisfies live-readiness when passed, waived, or N/A. */
    public const SATISFYING = [self::STATUS_PASSED, self::STATUS_WAIVED, self::STATUS_NOT_APPLICABLE];

    protected $fillable = [
        'checklist_id',
        'item_key',
        'status',
        'is_required',
        'evidence_reference',
        'notes',
        'waiver_reason',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(IntegrationProviderChecklist::class, 'checklist_id');
    }

    public function isSatisfied(): bool
    {
        return ! $this->is_required || in_array($this->status, self::SATISFYING, true);
    }
}

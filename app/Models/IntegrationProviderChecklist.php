<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Go-live checklist for an integration provider. A provider is only "live ready"
 * when all required items pass or are waived (with reason) and the required
 * sign-offs are recorded.
 */
class IntegrationProviderChecklist extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY = 'ready';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'integration_provider_id',
        'status',
        'live_ready',
        'finance_signoff_by',
        'finance_signoff_at',
        'it_signoff_by',
        'it_signoff_at',
        'approved_by',
        'approved_at',
        'notes',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'live_ready' => 'boolean',
            'finance_signoff_at' => 'datetime',
            'it_signoff_at' => 'datetime',
            'approved_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }

    public function items()
    {
        return $this->hasMany(IntegrationProviderChecklistItem::class, 'checklist_id');
    }
}

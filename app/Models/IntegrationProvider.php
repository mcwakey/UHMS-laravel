<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A configured external integration provider (SMS or Payment).
 *
 * Credentials are NOT stored on this row — see IntegrationProviderCredential.
 * The "only one active provider per module_type" rule is enforced by
 * IntegrationProviderService inside a transaction (MariaDB partial unique
 * indexes are unreliable here).
 */
class IntegrationProvider extends Model
{
    use HasFactory;

    public const MODULE_SMS = 'sms';
    public const MODULE_PAYMENT = 'payment';

    public const ENV_SANDBOX = 'sandbox';
    public const ENV_LIVE = 'live';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'module_type',
        'code',
        'name',
        'description',
        'environment',
        'base_url',
        'status',
        'is_active',
        'supports_send',
        'supports_status_check',
        'supports_callback',
        'supports_collection',
        'supports_disbursement',
        'supports_refund',
        'supports_balance_check',
        'sender_id',
        'callback_url',
        'webhook_secret_hint',
        'require_signature',
        'allow_unsigned_sandbox_callbacks',
        'signature_header',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'last_success_at',
        'last_failure_at',
        'last_error_message',
        'metadata_snapshot',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'supports_send' => 'boolean',
            'supports_status_check' => 'boolean',
            'supports_callback' => 'boolean',
            'supports_collection' => 'boolean',
            'supports_disbursement' => 'boolean',
            'supports_refund' => 'boolean',
            'supports_balance_check' => 'boolean',
            'require_signature' => 'boolean',
            'allow_unsigned_sandbox_callbacks' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    /* ── Relationships ──────────────────────────────────────────────── */

    public function credentials()
    {
        return $this->hasMany(IntegrationProviderCredential::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ── Scopes ─────────────────────────────────────────────────────── */

    public function scopeModule($query, string $moduleType)
    {
        return $query->where('module_type', $moduleType);
    }

    public function scopeSms($query)
    {
        return $query->where('module_type', self::MODULE_SMS);
    }

    public function scopePayment($query)
    {
        return $query->where('module_type', self::MODULE_PAYMENT);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', self::STATUS_ACTIVE);
    }

    /* ── Helpers ────────────────────────────────────────────────────── */

    /** The static registry entry (config/integrations.php) for this provider. */
    public function registryConfig(): array
    {
        return (array) (config("integrations.providers.{$this->module_type}.{$this->code}") ?? []);
    }

    public function isFake(): bool
    {
        return (bool) ($this->registryConfig()['is_fake'] ?? false);
    }

    /** Resolve the effective API base URL: stored value, else registry per-env. */
    public function resolveBaseUrl(): ?string
    {
        if (! empty($this->base_url)) {
            return $this->base_url;
        }

        $cfg = $this->registryConfig();
        return $this->environment === self::ENV_LIVE
            ? ($cfg['live_url'] ?? null)
            : ($cfg['sandbox_url'] ?? null);
    }
}

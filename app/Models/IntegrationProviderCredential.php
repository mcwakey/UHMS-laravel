<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single encrypted credential value for an integration provider.
 *
 * `encrypted_value` uses Laravel's `encrypted` cast so the plaintext never
 * touches the database. Values must never be rendered to Blade or included in
 * exception/log output — callers read decrypted values only inside adapters.
 */
class IntegrationProviderCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_provider_id',
        'credential_key',
        'encrypted_value',
        'is_sensitive',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'encrypted_value',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_value' => 'encrypted',
            'is_sensitive' => 'boolean',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }
}

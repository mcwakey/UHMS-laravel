<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analyzer extends Model
{
    protected $fillable = [
        'name',
        'model',
        'manufacturer',
        'protocol',
        'connection_type',
        'ip_address',
        'port',
        'com_port',
        'baud_rate',
        'is_active',
        'last_connected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime',
        'port' => 'integer',
        'baud_rate' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function testMappings(): HasMany
    {
        return $this->hasMany(AnalyzerTestMapping::class);
    }

    public function rawMessages(): HasMany
    {
        return $this->hasMany(AnalyzerRawMessage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTcp($query)
    {
        return $query->where('connection_type', 'tcp');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getConnectionInfoAttribute(): string
    {
        if ($this->connection_type === 'tcp') {
            return "{$this->ip_address}:{$this->port}";
        }

        return "{$this->com_port} @ {$this->baud_rate} baud";
    }

    public function getProtocolLabelAttribute(): string
    {
        return match ($this->protocol) {
            'hl7' => 'HL7 v2.x',
            'astm' => 'ASTM E1394',
            default => strtoupper($this->protocol),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Find the LabTest ID mapped to a given analyzer test code.
     */
    public function mapTestCode(string $analyzerCode): ?AnalyzerTestMapping
    {
        return $this->testMappings()
            ->where('analyzer_test_code', $analyzerCode)
            ->first();
    }
}

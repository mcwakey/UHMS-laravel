<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class SideEffectIsolationRegistry
{
    /** @var array<string, ProhibitedSubsystem> */
    private array $subsystems;

    /** @var array<string, OutboundChannel> */
    private array $outboundChannels;

    /**
     * @param  array<int, string|ProhibitedSubsystem>  $configured
     * @param  array<int, string|OutboundChannel>|null  $outboundDenyList
     */
    public function __construct(array $configured, ?array $outboundDenyList = null)
    {
        $subsystems = [];
        foreach ($configured as $value) {
            $subsystem = $value instanceof ProhibitedSubsystem
                ? $value
                : ProhibitedSubsystem::tryFrom(trim((string) $value));
            if ($subsystem === null) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_SUBSYSTEM_UNKNOWN', 'The isolation registry contains an unknown subsystem.');
            }
            $subsystems[$subsystem->value] = $subsystem;
        }

        foreach (ProhibitedSubsystem::cases() as $required) {
            if (! isset($subsystems[$required->value])) {
                throw new RuntimeIsolationException('FOUNDATION_ISOLATION_REGISTRY_INCOMPLETE', 'The required isolation registry is incomplete.');
            }
        }

        ksort($subsystems, SORT_STRING);
        $this->subsystems = $subsystems;

        $outboundChannels = [];
        foreach ($outboundDenyList ?? OutboundChannel::cases() as $value) {
            $channel = $value instanceof OutboundChannel
                ? $value
                : OutboundChannel::tryFrom(trim((string) $value));
            if ($channel === null) {
                throw new RuntimeIsolationException('FOUNDATION_OUTBOUND_CHANNEL_UNKNOWN', 'The outbound deny-list contains an unknown channel.');
            }
            $outboundChannels[$channel->value] = $channel;
        }
        foreach (OutboundChannel::cases() as $required) {
            if (! isset($outboundChannels[$required->value])) {
                throw new RuntimeIsolationException('FOUNDATION_OUTBOUND_DENY_LIST_INCOMPLETE', 'The outbound integration deny-list is incomplete.');
            }
        }
        ksort($outboundChannels, SORT_STRING);
        $this->outboundChannels = $outboundChannels;
    }

    public static function complete(): self
    {
        return new self(ProhibitedSubsystem::cases(), OutboundChannel::cases());
    }

    /** @param array<string, mixed> $configuration */
    public static function fromConfiguration(array $configuration): self
    {
        $isolation = $configuration['isolation'] ?? null;
        $configured = is_array($isolation) ? ($isolation['required_subsystems'] ?? null) : null;
        $outbound = is_array($isolation) ? ($isolation['outbound_deny_list'] ?? null) : null;
        if (! is_array($configured) || ! is_array($outbound)) {
            throw new RuntimeIsolationException('FOUNDATION_ISOLATION_CONFIG_MISSING', 'The required isolation subsystem configuration is missing.');
        }

        return new self($configured, $outbound);
    }

    /** @return array<int, ProhibitedSubsystem> */
    public function all(): array
    {
        return array_values($this->subsystems);
    }

    public function denies(ProhibitedSubsystem $subsystem): bool
    {
        return isset($this->subsystems[$subsystem->value]);
    }

    public function deniesOutbound(OutboundChannel $channel): bool
    {
        return isset($this->outboundChannels[$channel->value]);
    }
}

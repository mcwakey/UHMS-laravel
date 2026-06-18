<?php

namespace App\Exceptions\Integrations;

use RuntimeException;

/**
 * Controlled, user-safe integration error. Carries a translation key so the
 * controller can flash a localised, non-technical message — never a stack
 * trace or provider internals.
 */
class IntegrationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $translationKey = 'integrations.errors.generic',
        public readonly array $translationParams = [],
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(string $module): self
    {
        $key = $module === 'sms'
            ? 'integrations.errors.sms_not_configured'
            : 'integrations.errors.payment_not_configured';

        return new self("No active {$module} provider is configured.", $key);
    }

    public function localisedMessage(): string
    {
        return __($this->translationKey, $this->translationParams);
    }
}

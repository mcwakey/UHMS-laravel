<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use InvalidArgumentException;
use JsonException;

final class ProtectedToken
{
    private const PREFIX = 'lmt1';

    public function __construct(
        private readonly string $environment,
        private readonly string $domain,
        private readonly string $keyId,
        private readonly string $keyVersion,
        private readonly string $canonicalizationVersion,
        private readonly string $digest,
    ) {
        if (strlen($this->digest) !== 32) {
            throw new InvalidArgumentException('Protected token is invalid [LM-SEC-TOKEN-001].');
        }
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function keyVersion(): string
    {
        return $this->keyVersion;
    }

    public function canonicalizationVersion(): string
    {
        return $this->canonicalizationVersion;
    }

    /**
     * Fixed-width non-secret lookup value for protected-store CHAR(64)
     * columns. Callers must persist the token domain and key/canonicalization
     * versions alongside it; this digest is not a standalone token envelope.
     */
    public function lookupDigest(): string
    {
        return bin2hex($this->digest);
    }

    public function encode(): string
    {
        $header = json_encode([
            'canonicalization_version' => $this->canonicalizationVersion,
            'domain' => $this->domain,
            'environment' => $this->environment,
            'key_id' => $this->keyId,
            'key_version' => $this->keyVersion,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return self::PREFIX.'.'.self::base64UrlEncode($header).'.'.self::base64UrlEncode($this->digest);
    }

    public static function parse(string $encoded): self
    {
        try {
            $parts = explode('.', $encoded);
            if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
                throw new InvalidArgumentException;
            }
            $header = json_decode(self::base64UrlDecode($parts[1]), true, flags: JSON_THROW_ON_ERROR);
            $digest = self::base64UrlDecode($parts[2]);
            $required = ['environment', 'domain', 'key_id', 'key_version', 'canonicalization_version'];
            if (! is_array($header) || array_keys($header) !== $required) {
                // json_encode sorts here by the declaration order above. Exact fields prevent ambiguous envelopes.
                $expected = array_fill_keys($required, null);
                if (! is_array($header) || array_diff_key($expected, $header) !== [] || array_diff_key($header, $expected) !== []) {
                    throw new InvalidArgumentException;
                }
            }
            foreach ($required as $field) {
                if (! is_string($header[$field]) || $header[$field] === '') {
                    throw new InvalidArgumentException;
                }
            }

            return new self(
                $header['environment'],
                $header['domain'],
                $header['key_id'],
                $header['key_version'],
                $header['canonicalization_version'],
                $digest,
            );
        } catch (InvalidArgumentException|JsonException) {
            throw new InvalidArgumentException('Protected token is invalid [LM-SEC-TOKEN-PARSE-001].');
        }
    }

    public function matches(ProtectedToken $other): bool
    {
        if (! $this->hasSameContext($other)) {
            throw TokenContextMismatchException::create();
        }

        return hash_equals($this->digest, $other->digest);
    }

    public function hasSameContext(ProtectedToken $other): bool
    {
        return $this->environment === $other->environment
            && $this->domain === $other->domain
            && $this->keyId === $other->keyId
            && $this->keyVersion === $other->keyVersion
            && $this->canonicalizationVersion === $other->canonicalizationVersion;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            throw new InvalidArgumentException;
        }
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException;
        }

        return $decoded;
    }
}

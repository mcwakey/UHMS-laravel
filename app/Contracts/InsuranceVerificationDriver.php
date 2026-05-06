<?php

namespace App\Contracts;

use App\Support\Insurance\VerificationRequest;
use App\Support\Insurance\VerificationResult;

/**
 * Generic insurance verification driver contract.
 *
 * Implementations MUST NOT contain provider-specific branching. Each driver
 * represents a *style* of verification (manual, code-based, REST API, etc.).
 * Provider-specific configuration (URLs, code patterns, credential keys)
 * comes from the insurance_providers.verification_config column and the
 * config/insurance_verification.php file.
 */
interface InsuranceVerificationDriver
{
    /**
     * Run a verification attempt for the given request.
     */
    public function verify(VerificationRequest $request): VerificationResult;

    /**
     * Whether this driver requires a reference code to be entered/captured
     * by the operator (used by the UI to render the input field).
     */
    public function requiresReferenceCode(): bool;

    /**
     * Short name describing the driver (matches its key in config).
     */
    public function name(): string;
}

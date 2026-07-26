<?php

namespace App\Support\Maternity;

use Illuminate\Support\Facades\Route;

/**
 * Phase 14R.5 — safe cross-module return context.
 *
 * Cross-module navigation must preserve where the clinician came from WITHOUT
 * accepting an arbitrary URL. This value object therefore carries a NAMED
 * INTERNAL ROUTE plus its parameters — never a raw URL — so:
 *
 *   - an open redirect is structurally impossible (an external host can never
 *     be expressed);
 *   - the module set is closed (consultation / emergency / admission /
 *     maternity);
 *   - an unknown or unregistered route falls back to null, and the caller then
 *     uses the target module's normal show page.
 *
 * Authorisation is NOT encoded here: the return route runs through its own
 * middleware and policies exactly as if the clinician had navigated to it
 * directly, so a link can never grant access the user does not already have.
 */
final class MaternityReturnContext
{
    public const MODULE_CONSULTATION = 'consultation';
    public const MODULE_EMERGENCY = 'emergency';
    public const MODULE_ADMISSION = 'admission';
    public const MODULE_MATERNITY = 'maternity';

    /** Closed module set — anything else is rejected. */
    private const MODULES = [
        self::MODULE_CONSULTATION,
        self::MODULE_EMERGENCY,
        self::MODULE_ADMISSION,
        self::MODULE_MATERNITY,
    ];

    /**
     * Route-name prefixes each module may return to. A return context can never
     * point at, say, an export or a destructive endpoint in another area.
     */
    private const ALLOWED_ROUTE_PREFIXES = [
        self::MODULE_CONSULTATION => ['admin.consultations.', 'consultations.'],
        self::MODULE_EMERGENCY => ['admin.emergency.', 'emergency.'],
        self::MODULE_ADMISSION => ['admin.admissions.', 'admissions.'],
        self::MODULE_MATERNITY => ['admin.maternity.', 'maternity.'],
    ];

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function __construct(
        public readonly string $module,
        public readonly string $routeName,
        public readonly array $parameters,
        public readonly ?string $anchor = null,
    ) {}

    /**
     * Build a return context, or null when the request is not something we are
     * willing to redirect back to.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function make(
        string $module,
        string $routeName,
        array $parameters = [],
        ?string $anchor = null,
    ): ?self {
        if (! in_array($module, self::MODULES, true)) {
            return null;
        }

        if (! Route::has($routeName)) {
            return null;
        }

        $allowed = false;
        foreach (self::ALLOWED_ROUTE_PREFIXES[$module] as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            return null;
        }

        return new self($module, $routeName, self::scalarParameters($parameters), self::safeAnchor($anchor));
    }

    /**
     * Rebuild from request input (typically hidden form fields). Returns null
     * for anything that does not validate — an invalid return context is never
     * an error, it just means "use the normal show page".
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): ?self
    {
        $module = $input['return_module'] ?? null;
        $routeName = $input['return_route'] ?? null;

        if (! is_string($module) || ! is_string($routeName)) {
            return null;
        }

        $parameters = $input['return_parameters'] ?? [];

        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);
            $parameters = is_array($decoded) ? $decoded : [];
        }

        return self::make(
            $module,
            $routeName,
            is_array($parameters) ? $parameters : [],
            is_string($input['return_anchor'] ?? null) ? $input['return_anchor'] : null,
        );
    }

    /**
     * The resolved internal URL, or null when the route can no longer be built
     * (for example a record was deleted). Callers fall back to their own page.
     */
    public function url(): ?string
    {
        try {
            $url = route($this->routeName, $this->parameters);
        } catch (\Throwable) {
            return null;
        }

        return $this->anchor ? $url.'#'.$this->anchor : $url;
    }

    /**
     * Hidden-field payload for a form that should come back here.
     *
     * @return array<string, string>
     */
    public function toFormFields(): array
    {
        return array_filter([
            'return_module' => $this->module,
            'return_route' => $this->routeName,
            'return_parameters' => json_encode($this->parameters),
            'return_anchor' => $this->anchor,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'route' => $this->routeName,
            'parameters' => $this->parameters,
            'anchor' => $this->anchor,
            'url' => $this->url(),
        ];
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    /**
     * Route parameters are restricted to scalars. Nested arrays and objects
     * cannot be expressed, which keeps the payload trivially validatable.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private static function scalarParameters(array $parameters): array
    {
        $safe = [];

        foreach ($parameters as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_int($value) || is_string($value) || is_bool($value) || is_float($value)) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    /** Anchors are limited to a simple slug — never markup or a URL. */
    private static function safeAnchor(?string $anchor): ?string
    {
        if ($anchor === null) {
            return null;
        }

        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $anchor) ?? '';

        return $clean === '' ? null : substr($clean, 0, 64);
    }
}

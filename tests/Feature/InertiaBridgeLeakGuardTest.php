<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Static-analysis guardrail tests that scan Blade views for known full-reload
 * leak patterns. See docs/INERTIA_FULL_RELOAD_ANALYSIS.md for context.
 *
 * These tests do NOT boot a browser — they scan source files directly so they
 * are cheap and run on every CI build. They fail loudly when a developer
 * reintroduces a `window.location.href = '...'`, `location.reload()`, or
 * similar bypass without an explicit fallback chain.
 */
class InertiaBridgeLeakGuardTest extends TestCase
{
    /**
     * Files & line patterns that are intentionally allowed because they are
     * inside an `if (window.UhmsInertia) { ... } else { window.location... }`
     * fallback branch. Keyed by relative path; values are line snippets we
     * recognise as the safe fallback form.
     */
    private const ALLOWED_FALLBACKS = [
        'resources/views/billing/invoices/create.blade.php' => [
            'window.location.href = url;',
        ],
        'resources/views/lab/process.blade.php' => [
            'window.location.href = target;',
        ],
        // Accept-and-bill partial: guarded by `if (window.UhmsInertia && data.redirect)`
        // with a hard-navigation fallback only when the bridge is absent.
        'resources/views/lab/partials/accept-bill.blade.php' => [
            'window.location.href = target;',
        ],
        'resources/views/consultations/show.blade.php' => [
            'window.location.reload();',
        ],
        'resources/views/queue/board.blade.php' => [
            'location.reload()', // appears inside the UhmsInertia ternary fallback
        ],
        'resources/views/notifications/index.blade.php' => [
            'location.reload();',
        ],
        // Already the guarded UhmsInertia ternary (bridge-aware, falls back only
        // when the bridge is absent).
        'resources/views/emergency/board.blade.php' => [
            'window.UhmsInertia.reload({ preserveScroll: true }) : location.reload()',
        ],
        // Friendly error pages use a standalone layout with no Inertia bridge, so a
        // hard reload is the correct "try again" affordance (Phase 4).
        'resources/views/errors/partials/actions.blade.php' => [
            'window.location.reload();',
        ],
        // Anti-clickjacking frame-breaker: hard-navigating the top frame is the
        // entire purpose; it must not route through the SPA bridge.
        'resources/views/layouts/partials/frame-breaker.blade.php' => [
            'window.top.location.replace(window.location.href);',
            'window.location.replace(window.location.href);',
        ],
    ];

    /** Patterns that indicate a hard reload / page-blow-up. */
    private const FORBIDDEN_PATTERNS = [
        '/window\.location\.href\s*=/i',
        '/window\.location\.reload\s*\(/i',
        '/window\.location\.assign\s*\(/i',
        '/window\.location\.replace\s*\(/i',
        '/(?<!window\.)location\.reload\s*\(/i',
        '/(?<!window\.)location\.assign\s*\(/i',
        '/(?<!window\.)location\.replace\s*\(/i',
    ];

    public function test_no_blade_view_introduces_unguarded_full_reload(): void
    {
        $viewsRoot = base_path('resources/views');
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $allowed = self::ALLOWED_FALLBACKS[$rel] ?? [];

            $contents = file_get_contents($file->getPathname());
            $lines = preg_split("/\r\n|\n|\r/", $contents);

            foreach ($lines as $i => $line) {
                $trim = trim($line);
                if ($trim === '' || str_starts_with($trim, '//') || str_starts_with($trim, '*')) {
                    continue;
                }

                foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                    if (!preg_match($pattern, $line)) {
                        continue;
                    }

                    // Allow when the surrounding code is the documented
                    // UhmsInertia fallback branch.
                    $isAllowed = false;
                    foreach ($allowed as $allowedSnippet) {
                        if (str_contains($line, $allowedSnippet)) {
                            $isAllowed = true;
                            break;
                        }
                    }
                    if ($isAllowed) {
                        continue;
                    }

                    $offenders[] = sprintf('%s:%d  %s', $rel, $i + 1, $trim);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Blade views must route navigation/reloads through window.UhmsInertia "
            . "(see docs/INERTIA_FULL_RELOAD_ANALYSIS.md). Offenders:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function test_blade_views_do_not_reintroduce_data_inertia_optin(): void
    {
        // The bridge is now default-on for forms; `data-inertia` is no longer
        // required and should NOT be reintroduced as the opt-in marker (it
        // confuses future devs about what's intercepted).
        $viewsRoot = base_path('resources/views');
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            if (preg_match_all('/\bdata-inertia\b(?!-)/', $contents, $m)) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                $offenders[] = $rel . ' (' . count($m[0]) . 'x)';
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Found `data-inertia` opt-in markers — forms are now default-on. "
            . "Use `data-no-inertia` instead to opt OUT. Offenders:\n  - "
            . implode("\n  - ", $offenders)
        );
    }
}

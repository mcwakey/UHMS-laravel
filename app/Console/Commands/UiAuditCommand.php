<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * UHMS UI governance audit.
 *
 * Static analysis of the Blade/Vue UI layer against the UHMS design law
 * (docs/UHMS_UI_THEME_RULES.md + docs/UI_COMPONENT_STANDARDS.md). It does NOT
 * change code — it reports drift so reviewers (and AI coding agents) catch UI
 * regressions before they land.
 *
 * Detects: forbidden patterns (inline workflow badges, raw status echoes, raw
 * technical errors, foreign CSS frameworks), component-adoption gaps (page
 * header / empty state / stat card / responsive tables / confirm-form), config
 * status-domain integrity, accessibility risks (icon-only buttons, colour-only
 * status, missing alt), print-layout adoption, and possible missing permission
 * guards.
 *
 *   php artisan ui:audit                 # human report + storage/reports/ui-audit-report.md
 *   php artisan ui:audit --json          # also storage/reports/ui-audit-report.json
 *   php artisan ui:audit --path=resources/views/admin/patients
 *   php artisan ui:audit --strict        # promote softer heuristics to findings
 *   php artisan ui:audit --fail          # non-zero exit if NEW critical findings exist
 *   php artisan ui:audit --update-baseline   # snapshot current debt as the baseline
 */
class UiAuditCommand extends Command
{
    protected $signature = 'ui:audit
        {--json : Also write a JSON report to storage/reports/ui-audit-report.json}
        {--fail : Exit non-zero when NEW critical findings exist (outside the baseline)}
        {--strict : Enable stricter heuristics that may flag valid-but-risky code}
        {--path= : Limit the scan to a path relative to the project root}
        {--update-baseline : Rewrite the UI debt baseline from this run and exit}';

    protected $description = 'Audit the UHMS UI (Blade/Vue) against the design system and report violations.';

    /** Allowed Bootstrap contextual variants for status colours. */
    private const VARIANTS = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    /** Domains that MUST exist in config/ui.php (see docs/UI_COMPONENT_STANDARDS.md). */
    private const REQUIRED_DOMAINS = [
        'visit', 'invoice', 'payment', 'mar', 'stock', 'requisition', 'emergency',
        'triage', 'disposition', 'theatre', 'lab', 'blood_unit', 'blood_request',
        'blood_issue', 'crossmatch', 'screening', 'donor_screening', 'claim',
        'priority', 'default',
    ];

    private const SEVERITIES = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO'];

    private const BASELINE_PATH = 'app/ui-audit-baseline.json';

    /** @var array<int,array{severity:string,type:string,file:string,line:int,message:string,recommendation:string}> */
    private array $findings = [];

    private bool $strict = false;

    private int $bladeCount = 0;
    private int $vueCount = 0;
    private int $cssCount = 0;

    public function handle(): int
    {
        $this->strict = (bool) $this->option('strict');

        // ── Gather files ─────────────────────────────────────────────
        $relPath = $this->option('path');
        $roots = $relPath
            ? [base_path($relPath)]
            : [resource_path('views'), resource_path('js'), resource_path('css')];

        foreach ($roots as $root) {
            if (! is_dir($root) && ! is_file($root)) {
                continue;
            }
            $files = is_file($root) ? [new \SplFileInfo($root)] : File::allFiles($root);
            foreach ($files as $file) {
                $path = $file->getPathname();
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['php', 'vue', 'js', 'css'], true)) {
                    continue;
                }
                if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
                    || str_contains($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR)) {
                    continue;
                }
                $rel = $this->relative($path);
                $content = @file_get_contents($path);
                if ($content === false) {
                    continue;
                }

                if (str_ends_with($path, '.blade.php')) {
                    $this->bladeCount++;
                    $this->scanBlade($rel, $content);
                } elseif ($ext === 'vue') {
                    $this->vueCount++;
                    $this->scanVue($rel, $content);
                } elseif ($ext === 'js') {
                    $this->scanScript($rel, $content);
                } elseif ($ext === 'css') {
                    $this->cssCount++;
                    $this->scanStyle($rel, $content);
                }
            }
        }

        // config/ui.php status-domain integrity (skipped when --path narrows the scan)
        if (! $relPath) {
            $this->auditStatusDomains();
        }

        // ── Baseline ─────────────────────────────────────────────────
        if ($this->option('update-baseline')) {
            return $this->writeBaseline();
        }

        $baseline = $this->loadBaseline();

        // ── Output ───────────────────────────────────────────────────
        $counts = $this->severityCounts();
        $this->renderConsole($counts);
        $this->writeMarkdownReport($counts, $baseline);
        if ($this->option('json')) {
            $this->writeJsonReport($counts, $baseline);
        }

        // ── Exit code ────────────────────────────────────────────────
        if ($this->option('fail')) {
            $blockers = $this->newBlockers($baseline);
            if ($blockers > 0) {
                $this->error(sprintf(
                    'ui:audit --fail: %d new %s finding(s) outside the baseline.',
                    $blockers,
                    $this->strict ? 'critical/high' : 'critical'
                ));
                return self::FAILURE;
            }
            $this->info('ui:audit --fail: no new blocking findings. ✔');
        }

        return self::SUCCESS;
    }

    // =================================================================
    //  Blade scanning
    // =================================================================

    private function scanBlade(string $file, string $content): void
    {
        // Blade comments hold example/disabled markup — blank them (length-preserving,
        // so line numbers stay accurate) so doc examples are never flagged.
        $content = $this->stripBladeComments($content);

        $isErrorPage = str_contains($file, '/errors/') || str_contains($file, '\\errors\\');
        $isComponent = str_contains($file, 'views/components/') || str_contains($file, 'views\\components\\');
        $isPrintView = $this->looksPrintable($file, $content);

        $this->detectForbidden($file, $content, $isErrorPage);
        $this->detectComponentGaps($file, $content, $isComponent);
        $this->detectAccessibility($file, $content);

        // Reusable components are building blocks (confirm-form, modal-popup …) — the
        // destructive/permission heuristics belong on the pages that consume them.
        if (! $isComponent) {
            $this->detectPermissionGuard($file, $content);
        }

        if ($isPrintView) {
            $this->detectPrintLayout($file, $content);
        }
    }

    private function stripBladeComments(string $content): string
    {
        return preg_replace_callback('/\{\{--.*?--\}\}/s', function ($m) {
            return preg_replace('/[^\n]/', ' ', $m[0]);
        }, $content) ?? $content;
    }

    private function scanVue(string $file, string $content): void
    {
        // Vue islands: only the universally-applicable checks apply.
        $this->detectForbidden($file, $content, false);
        $this->lineScan($file, $content, '/\bclass=["\'][^"\']*\bbg-(success|warning|danger|info|primary|secondary)\b[^"\']*["\'][^>]*>\s*\{\{[^}]*status/i',
            'LOW', 'vue-inline-status',
            'Vue component renders a status colour inline.',
            'Prefer a shared status helper / <Can> + status component to mirror Blade <x-status-badge>.');
    }

    private function scanScript(string $file, string $content): void
    {
        if (preg_match('/\b(tailwind|daisyui|bulma|chakra|@mui|material-ui)\b/i', $content)) {
            $this->add('HIGH', 'foreign-framework', $file, $this->firstLine($content, '/\b(tailwind|daisyui|bulma|chakra|@mui|material-ui)\b/i'),
                'Reference to a non-approved frontend framework.',
                'UHMS uses Bootstrap 5 + Tabler Icons only. Remove the foreign framework reference.');
        }
    }

    private function scanStyle(string $file, string $content): void
    {
        if (preg_match('/@tailwind|@apply|tailwindcss|daisyui|bulma/i', $content)) {
            $this->add('HIGH', 'foreign-framework', $file, $this->firstLine($content, '/@tailwind|@apply|tailwindcss|daisyui|bulma/i'),
                'CSS references a non-approved framework (Tailwind/Bulma/DaisyUI).',
                'UHMS uses Bootstrap 5 only. Remove the foreign framework directives.');
        }
    }

    // =================================================================
    //  3. Forbidden patterns
    // =================================================================

    private function detectForbidden(string $file, string $content, bool $isErrorPage): void
    {
        // 3.1 Raw technical errors in views — CRITICAL (error pages may legitimately
        //     surface a code label, but never dd/dump/var_dump/print_r/SQLSTATE).
        $this->lineScan($file, $content, '/\b(dd|dump|var_dump|print_r)\s*\(/',
            'CRITICAL', 'debug-output-in-view',
            'Debug output function left in a view.',
            'Remove dd()/dump()/var_dump()/print_r(). Never expose internals to users.');

        if (! $isErrorPage) {
            $this->lineScan($file, $content, '/\b(SQLSTATE|QueryException|Stack trace|getTraceAsString)\b/',
                'CRITICAL', 'raw-technical-error',
                'Raw technical error text rendered in a view.',
                'Do not expose technical errors. Log them and show a friendly message (Phase 4 error pages).');
        }

        // 3.2 Foreign frameworks — HIGH.
        $this->lineScan($file, $content, '/\b(tailwind|daisyui|bulma|chakra-ui|material-ui)\b/i',
            'HIGH', 'foreign-framework',
            'Reference to a non-approved CSS framework.',
            'UHMS uses Bootstrap 5 + Tabler Icons only.');

        // 3.3 Inline workflow status badges — HIGH when tied to a status value.
        //     Flags `bg-{{ $x->status->color() }}` / `bg-{{ ...status... }}` style badges.
        $this->lineScan($file, $content, '/badge\s+bg-\{\{[^}]*(->color\(\)|status|->state|triage|priority)[^}]*\}\}/i',
            'HIGH', 'inline-workflow-badge',
            'Workflow status rendered with an inline Bootstrap colour.',
            'Use <x-status-badge :status="$model->status" domain="..."/> so the colour is centralised in config/ui.php.');

        // Literal decorative badges only matter in --strict.
        if ($this->strict) {
            $this->lineScan($file, $content, '/badge\s+bg-(success|warning|danger|info|primary|secondary|dark)\b(?![\w-])/',
                'LOW', 'literal-badge',
                'Literal Bootstrap badge colour (strict mode).',
                'If this represents a workflow status, use <x-status-badge>. Decorative badges may stay.');
        }

        // 3.4 Raw status echo without formatting — HIGH (UPPER_SNAKE leaks to users).
        $this->lineScan($file, $content, '/\{\{\s*\$[\w()>-]*->(status|payment_status|triage_category|emergency_status|state)\s*\}\}/',
            'HIGH', 'raw-status-echo',
            'Raw status value echoed directly (may render UPPER_SNAKE / enum to the user).',
            'Use <x-status-badge :status="..."/> or ->label() so users see human text and a colour.');

        // 3.5 Excessive inline styles — LOW (print views are allowed inline styles).
        if (! $this->looksPrintable($file, $content)) {
            $count = preg_match_all('/\sstyle=("|\')/', $content);
            if ($count > 0 && ($this->strict || $count >= 3)) {
                $this->add('LOW', 'inline-style', $file, $this->firstLine($content, '/\sstyle=("|\')/'),
                    sprintf('%d inline style attribute(s).', $count),
                    'Prefer Bootstrap utilities / UHMS design tokens. Document unavoidable print-only inline styles.');
            }
        }
    }

    // =================================================================
    //  4. Component-adoption gaps
    // =================================================================

    private function detectComponentGaps(string $file, string $content, bool $isComponent): void
    {
        $isPartial = str_contains(basename($file), '_') || str_contains($file, '/partials/') || str_contains($file, '\\partials\\');

        // 4.1 Page header — MEDIUM. A full page with a header bar (near the top) but
        //     no <x-page-header>.
        $top = substr($content, 0, 1800);
        if (! $isComponent && ! $isPartial
            && str_contains($content, "@extends('layouts.app')")
            && ! str_contains($content, '<x-page-header')
            && preg_match('/justify-content-between[^>]*>(?:(?!<\/div>).)*(border-bottom|page-title|<h[1-4])/s', $top)) {
            $this->add('MEDIUM', 'missing-page-header', $file, $this->firstLine($content, '/justify-content-between/'),
                'Custom header bar instead of the shared page-header component.',
                'Use <x-page-header title="..." icon="..."> with an <x-slot:actions> for buttons.');
        }

        // 4.2 Empty state — MEDIUM. Plain "no records" text, no <x-empty-state>.
        if (! str_contains($content, '<x-empty-state')
            && preg_match('/(No records found|No data found|Nothing found|No results found|No items available|No data available)/i', $content, $m, PREG_OFFSET_CAPTURE)) {
            $this->add('MEDIUM', 'missing-empty-state', $file, $this->lineAt($content, $m[0][1]),
                'Plain empty-list message instead of the shared empty-state component.',
                'Use <x-empty-state icon="..." title="..." message="..."/>.');
        }

        // 4.3 KPI / stat cards — LOW. Repeated "border-start" KPI cards, no <x-stat-card>.
        if (! str_contains($content, '<x-stat-card')
            && preg_match_all('/card[^"]*border-start/', $content) >= 3) {
            $this->add('LOW', 'missing-stat-card', $file, $this->firstLine($content, '/card[^"]*border-start/'),
                'Repeated KPI-card markup that could use the shared stat-card component.',
                'Use <x-stat-card label="..." :value="..." variant="..." icon="..."/>.');
        }

        // 4.4 Tables not wrapped responsively — MEDIUM (per table).
        $this->detectUnwrappedTables($file, $content);

        // 4.5 Destructive actions without a confirm — HIGH (skip reusable components,
        //     which include the confirmation building blocks themselves).
        if (! $isComponent) {
            $this->detectDestructive($file, $content);
        }
    }

    private function detectUnwrappedTables(string $file, string $content): void
    {
        if (preg_match_all('/<table\b[^>]*class="[^"]*\btable\b[^"]*"/i', $content, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $match) {
                $offset = $match[1];
                $before = substr($content, max(0, $offset - 220), min(220, $offset));
                if (str_contains($before, 'table-responsive') || str_contains($before, '<x-data-table')) {
                    continue;
                }
                $this->add('MEDIUM', 'table-not-responsive', $file, $this->lineAt($content, $offset),
                    'Data table is not wrapped in a responsive container.',
                    'Wrap in <div class="table-responsive"> or use <x-data-table>.');
            }
        }
    }

    private function detectDestructive(string $file, string $content): void
    {
        $verbs = $this->strict
            ? '(Delete|Reverse|Refund|Discard|Void|Merge|Mark\s+Deceased|Deactivate|Disable|Reject|Cancel)'
            : '(Delete|Reverse|Refund|Discard|Void|Merge|Mark\s+Deceased)';

        if (! preg_match_all('/>\s*(?:<i[^>]*><\/i>\s*)?' . $verbs . '\b/i', $content, $m, PREG_OFFSET_CAPTURE)) {
            return;
        }
        foreach ($m[0] as $match) {
            $offset = $match[1];
            $window = substr($content, max(0, $offset - 600), 900);
            // Must be an actual action control (button / submit / posting form / link).
            if (! preg_match('/<button|type="submit"|<x-confirm-form|method="POST"|<a\b/i', $window)) {
                continue;
            }
            // Already guarded by a confirmation mechanism?
            if (preg_match('/<x-confirm-form|uhmsConfirmSubmit|data-confirm|onsubmit=|confirm\(|swal|Swal|data-bs-toggle="modal"/i', $window)) {
                continue;
            }
            $this->add('HIGH', 'destructive-no-confirm', $file, $this->lineAt($content, $offset),
                'Destructive action with no confirmation step detected.',
                'Use <x-confirm-form> (or a confirm modal). High-risk actions should also require a reason.');
        }
    }

    // =================================================================
    //  7. Accessibility
    // =================================================================

    private function detectAccessibility(string $file, string $content): void
    {
        // 7.1 Icon-only button/link without an accessible name — MEDIUM.
        if (preg_match_all('/<(button|a)\b((?:(?!<\/?(?:button|a)\b).)*?)>\s*<i\s+class="ti\s+ti-[a-z0-9-]+[^"]*"><\/i>\s*<\/\1>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[2] as $i => $attrMatch) {
                $attrs = $attrMatch[0];
                if (preg_match('/aria-label|aria-hidden|\btitle=/i', $attrs)) {
                    continue;
                }
                $this->add('MEDIUM', 'icon-only-no-label', $file, $this->lineAt($content, $m[0][$i][1]),
                    'Icon-only button/link without aria-label or title.',
                    'Add aria-label and title describing the action (e.g. aria-label="Delete").');
            }
        }

        // 7.2 Colour-only status: an empty badge conveys state by colour alone — HIGH.
        if (preg_match_all('/<span[^>]*class="[^"]*\bbadge\b[^"]*bg-(?:success|warning|danger|info|primary|secondary|dark)[^"]*"[^>]*>\s*<\/span>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $match) {
                $this->add('HIGH', 'status-colour-only', $file, $this->lineAt($content, $match[1]),
                    'Status conveyed by colour only (empty badge — no text).',
                    'Never rely on colour alone. Add visible text or use <x-status-badge>.');
            }
        }

        // 7.3 Images without alt — LOW.
        if (preg_match_all('/<img\b((?:(?!>).)*)>/is', $content, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $i => $attrMatch) {
                if (! preg_match('/\balt=/i', $attrMatch[0])) {
                    $this->add('LOW', 'img-no-alt', $file, $this->lineAt($content, $m[0][$i][1]),
                        'Image without an alt attribute.',
                        'Add alt text (alt="" for purely decorative images).');
                }
            }
        }
    }

    // =================================================================
    //  6. Possible missing permission guard (file-level heuristic)
    // =================================================================

    private function detectPermissionGuard(string $file, string $content): void
    {
        if (str_contains($file, 'views/components/') || str_contains($file, 'views\\components\\')) {
            return;
        }
        $hasGuard = preg_match('/@can\b|@canany\b|@cannot\b|@role\b|<Can\b|\$can\b/i', $content);
        if ($hasGuard) {
            return;
        }
        // A page with action controls but zero permission gates is worth a human look.
        $actions = '(Create|Add|Edit|Update|Delete|Cancel|Approve|Reject|Verify|Submit|Dispense|Administer|Pay|Reverse|Refund|Transfer|Receive|Merge|Export|Assign|Complete|Reopen|Correct|Override|Disable)';
        if (preg_match('/<(?:a|button)\b[^>]*>(?:(?!<\/(?:a|button)>).)*?\b' . $actions . '\b/is', $content, $m, PREG_OFFSET_CAPTURE)
            && preg_match('/route\(|action=|method="POST"/i', $content)) {
            $this->add('LOW', 'possible-missing-permission', $file, 1,
                'Page has action controls but no @can/@canany guard (POSSIBLE missing permission guard).',
                'Confirm the action is permission-gated in the UI and backend. This is a warning for human review, not an auto-fix.');
        }
    }

    // =================================================================
    //  8. Print-layout adoption
    // =================================================================

    private function detectPrintLayout(string $file, string $content): void
    {
        if (str_contains($content, '<x-print-layout')
            || str_contains($content, "layouts.print")
            || preg_match('/@media\s+print/i', $content)) {
            return;
        }
        $this->add('INFO', 'print-no-layout', $file, 1,
            'Printable view does not use <x-print-layout>/layouts.print or @media print rules.',
            'Adopt <x-print-layout> (hospital header, patient context, black-on-white, d-print-none controls).');
    }

    // =================================================================
    //  5. config/ui.php status-domain integrity
    // =================================================================

    private function auditStatusDomains(): void
    {
        $file = 'config/ui.php';
        $statusMap = config('ui.status', []);

        foreach (self::REQUIRED_DOMAINS as $domain) {
            $map = $domain === 'priority' ? config('ui.priority') : ($statusMap[$domain] ?? null);
            if (! is_array($map) || $map === []) {
                $this->add('HIGH', 'status-domain-missing', $file, 1,
                    "Required status domain '{$domain}' is missing or empty in config/ui.php.",
                    "Add a '{$domain}' map of STATUS => bootstrap-variant so <x-status-badge domain=\"{$domain}\"> resolves correctly.");
                continue;
            }
            foreach ($map as $status => $variant) {
                if (! in_array($variant, self::VARIANTS, true)) {
                    $this->add('MEDIUM', 'status-invalid-variant', $file, 1,
                        "Domain '{$domain}' status '{$status}' maps to invalid variant '{$variant}'.",
                        'Use a Bootstrap variant: ' . implode(', ', self::VARIANTS) . '.');
                }
            }
        }

        if (! is_array(config('ui.dark_text_variants')) || ! in_array('warning', config('ui.dark_text_variants', []), true)) {
            $this->add('LOW', 'contrast-config', $file, 1,
                'dark_text_variants should include low-contrast tints (warning/info/light) for readable text.',
                'Keep warning/info/light in ui.dark_text_variants so badges add text-dark.');
        }
    }

    // =================================================================
    //  Helpers
    // =================================================================

    private function looksPrintable(string $file, string $content): bool
    {
        return (bool) (preg_match('/print|receipt|pdf/i', basename($file))
            || str_contains($content, 'window.print')
            || str_contains($content, 'onclick="window.print')
            || str_contains($content, "layouts.print")
            || str_contains($content, '<x-print-layout'));
    }

    private function lineScan(string $file, string $content, string $pattern, string $severity, string $type, string $message, string $rec): void
    {
        if (preg_match_all($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $match) {
                $this->add($severity, $type, $file, $this->lineAt($content, $match[1]), $message, $rec);
            }
        }
    }

    private function add(string $severity, string $type, string $file, int $line, string $message, string $rec): void
    {
        $this->findings[] = [
            'severity' => $severity,
            'type' => $type,
            'file' => str_replace('\\', '/', $file),
            'line' => $line,
            'message' => $message,
            'recommendation' => $rec,
        ];
    }

    private function lineAt(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function firstLine(string $content, string $pattern): int
    {
        return preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE) ? $this->lineAt($content, $m[0][1]) : 1;
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }

    /** @return array<string,int> */
    private function severityCounts(): array
    {
        $counts = array_fill_keys(self::SEVERITIES, 0);
        foreach ($this->findings as $f) {
            $counts[$f['severity']] = ($counts[$f['severity']] ?? 0) + 1;
        }
        return $counts;
    }

    private function fingerprint(array $f): string
    {
        return $f['type'] . '|' . $f['file'];
    }

    /** @return array<string,bool> */
    private function loadBaseline(): array
    {
        $path = storage_path(self::BASELINE_PATH);
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        return array_flip($data['fingerprints'] ?? []);
    }

    private function newBlockers(array $baseline): int
    {
        $blocking = $this->strict ? ['CRITICAL', 'HIGH'] : ['CRITICAL'];
        $n = 0;
        foreach ($this->findings as $f) {
            if (in_array($f['severity'], $blocking, true) && ! isset($baseline[$this->fingerprint($f)])) {
                $n++;
            }
        }
        return $n;
    }

    private function writeBaseline(): int
    {
        $fingerprints = [];
        foreach ($this->findings as $f) {
            $fingerprints[$this->fingerprint($f)] = true;
        }
        ksort($fingerprints);
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'note' => 'Known UHMS UI debt at baseline time. Future work should SHRINK this list, never grow it. Do not add new CRITICAL findings here to silence them.',
            'count' => count($fingerprints),
            'fingerprints' => array_keys($fingerprints),
        ];
        $path = storage_path(self::BASELINE_PATH);
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info(sprintf('Baseline written: %s (%d fingerprints).', $path, count($fingerprints)));
        return self::SUCCESS;
    }

    // =================================================================
    //  Reporting
    // =================================================================

    private function renderConsole(array $counts): void
    {
        $this->newLine();
        $this->line('<options=bold>UHMS UI Audit Report</>');
        $this->line('====================');
        $this->line(sprintf('Blade views : %d', $this->bladeCount));
        $this->line(sprintf('Vue files   : %d', $this->vueCount));
        $this->line(sprintf('CSS files   : %d', $this->cssCount));
        $this->newLine();
        foreach (self::SEVERITIES as $sev) {
            $line = sprintf('  %-9s %d', $sev, $counts[$sev]);
            match ($sev) {
                'CRITICAL' => $counts[$sev] ? $this->error($line) : $this->line($line),
                'HIGH' => $counts[$sev] ? $this->warn($line) : $this->line($line),
                default => $this->line($line),
            };
        }
        $this->newLine();

        // Group a preview of top findings by severity.
        $byType = [];
        foreach ($this->findings as $f) {
            $byType[$f['severity']][$f['type']] = ($byType[$f['severity']][$f['type']] ?? 0) + 1;
        }
        foreach (self::SEVERITIES as $sev) {
            if (empty($byType[$sev])) {
                continue;
            }
            $this->line("<options=bold>{$sev}</>");
            arsort($byType[$sev]);
            foreach ($byType[$sev] as $type => $n) {
                $this->line(sprintf('  %-28s %d', $type, $n));
            }
        }
        $this->newLine();
        $this->info('Full report: storage/reports/ui-audit-report.md');
    }

    private function writeMarkdownReport(array $counts, array $baseline): void
    {
        $dir = storage_path('reports');
        @mkdir($dir, 0755, true);

        $lines = [];
        $lines[] = '# UHMS UI Audit Report';
        $lines[] = '';
        $lines[] = '_Generated ' . now()->toDayDateTimeString() . ' by `php artisan ui:audit`._';
        $lines[] = '';
        $lines[] = '## Scope';
        $lines[] = "- Blade views: {$this->bladeCount}";
        $lines[] = "- Vue components: {$this->vueCount}";
        $lines[] = "- CSS files: {$this->cssCount}";
        $lines[] = '';
        $lines[] = '## Severity summary';
        $lines[] = '';
        $lines[] = '| Severity | Count |';
        $lines[] = '|----------|------:|';
        foreach (self::SEVERITIES as $sev) {
            $lines[] = "| {$sev} | {$counts[$sev]} |";
        }
        $lines[] = '';

        // Group by severity then file.
        usort($this->findings, function ($a, $b) {
            $sa = array_search($a['severity'], self::SEVERITIES, true);
            $sb = array_search($b['severity'], self::SEVERITIES, true);
            return $sa <=> $sb ?: strcmp($a['file'], $b['file']) ?: $a['line'] <=> $b['line'];
        });

        foreach (self::SEVERITIES as $sev) {
            $group = array_values(array_filter($this->findings, fn ($f) => $f['severity'] === $sev));
            if ($group === []) {
                continue;
            }
            $lines[] = "## {$sev} (" . count($group) . ')';
            $lines[] = '';
            $shown = 0;
            foreach ($group as $f) {
                if ($shown++ >= 200) {
                    $lines[] = '- … and ' . (count($group) - 200) . ' more (see JSON report).';
                    break;
                }
                $base = isset($baseline[$this->fingerprint($f)]) ? ' _(baseline)_' : '';
                $lines[] = sprintf('- **%s** — `%s:%d`%s', $f['type'], $f['file'], $f['line'], $base);
                $lines[] = sprintf('  - %s', $f['message']);
                $lines[] = sprintf('  - → %s', $f['recommendation']);
            }
            $lines[] = '';
        }

        if ($this->findings === []) {
            $lines[] = '✅ No findings. The UI is compliant with the current ruleset.';
        }

        file_put_contents($dir . '/ui-audit-report.md', implode("\n", $lines) . "\n");
    }

    private function writeJsonReport(array $counts, array $baseline): void
    {
        $dir = storage_path('reports');
        @mkdir($dir, 0755, true);
        $findings = array_map(function ($f) use ($baseline) {
            $f['baseline'] = isset($baseline[$this->fingerprint($f)]);
            return $f;
        }, $this->findings);
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'scope' => [
                'blade_views' => $this->bladeCount,
                'vue_components' => $this->vueCount,
                'css_files' => $this->cssCount,
            ],
            'severity_counts' => $counts,
            'strict' => $this->strict,
            'findings' => $findings,
        ];
        file_put_contents($dir . '/ui-audit-report.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('JSON report: storage/reports/ui-audit-report.json');
    }
}

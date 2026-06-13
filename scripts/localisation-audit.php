<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$scanRoots = [
    'resources/views',
    'resources/js',
    'public/js',
    'app/Http/Controllers',
    'app/View/Components',
    'app/Models',
    'app/Enums',
    'app/Services',
    'app/Helpers',
    'resources/lang',
    'lang/en',
    'lang/fr',
    'routes',
];

$skipParts = [
    DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR,
];

$extensions = ['blade.php', 'php', 'js'];
$files = [];

foreach ($scanRoots as $scanRoot) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scanRoot);
    if (! is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $fullPath = $file->getPathname();
        foreach ($skipParts as $skipPart) {
            if (str_contains($fullPath, $skipPart)) {
                continue 2;
            }
        }

        foreach ($extensions as $extension) {
            if (str_ends_with($fullPath, $extension)) {
                $files[] = $fullPath;
                break;
            }
        }
    }
}

sort($files);

$patterns = [
    'blade_text' => [
        'regex' => '/<(?<tag>h[1-6]|label|button|th|td|option|a|span|p|div|small|strong|li|figcaption)\b[^>]*>\s*(?<text>[A-Z][^<>{}]{2,120})\s*<\/\k<tag>>/u',
        'priority' => 'high',
    ],
    'attribute' => [
        'regex' => '/\b(?<attr>placeholder|title|aria-label|alt|data-confirm|data-title|data-text)=["\'](?<text>[A-Z][^"\']{2,120})["\']/u',
        'priority' => 'high',
    ],
    'blade_section' => [
        'regex' => '/@section\(\s*[\'"]title[\'"]\s*,\s*[\'"](?<text>[A-Z][^\'"]{2,120})[\'"]\s*\)/u',
        'priority' => 'high',
    ],
    'controller_flash' => [
        'regex' => '/->with\(\s*[\'"](?:success|error|warning|info|status)[\'"]\s*,\s*[\'"](?<text>[^\'"]{3,160})[\'"]\s*\)/u',
        'priority' => 'high',
    ],
    'php_label_array' => [
        'regex' => '/[\'"](?:label|title|description|message|heading|button|placeholder)[\'"]\s*=>\s*[\'"](?<text>[A-Z][^\'"]{2,160})[\'"]/u',
        'priority' => 'medium',
    ],
    'php_return_label' => [
        'regex' => '/return\s+[\'"](?<text>[A-Z][^\'"]{2,120})[\'"]\s*;/u',
        'priority' => 'medium',
    ],
    'js_ui' => [
        'regex' => '/(?:alert|confirm)\(\s*[\'"](?<text>[^\'"]{3,160})[\'"]\s*\)|(?:text|title|placeholder|label|message)\s*:\s*[\'"](?<text2>[A-Z][^\'"]{2,160})[\'"]/u',
        'priority' => 'medium',
    ],
];

$ignoreExact = [
    'N/A', 'NA', 'ID', 'UHMS', 'PDF', 'CSV', 'Excel', 'JSON', 'API', 'URL', 'HTTP', 'HTTPS',
    'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'GH₵', 'GHS',
];

$ignoreRegex = [
    '/^[-–—]+$/u',
    '/^[A-Z0-9_ .\/:-]+$/u',
    '/^[\d\s.,:%()\/+-]+$/u',
    '/^(active|inactive|pending|completed|cancelled|approved|rejected)$/i',
    '/^(GET|POST|PUT|PATCH|DELETE|HEAD)$/',
    '/^(id|uuid|slug|name|email|password|status|created_at|updated_at)$/i',
    '/^(ti|fa|btn|card|modal|table|row|col|form|data|aria|href|src)[\w -]*$/i',
    '/^(mmHg|bpm|kg|ml|°C|%)$/u',
    '/^GHA-[X-]+$/',
    '/^\{\{.*\}\}$/',
    '/^__\(/',
    '/^route\(/',
    '/^asset\(/',
    '/^config\(/',
    '/^env\(/',
];

function relative_path(string $root, string $path): string
{
    return str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));
}

function normalize_text(string $text): string
{
    $text = html_entity_decode(trim(strip_tags($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return trim($text);
}

function should_ignore(string $text, array $ignoreExact, array $ignoreRegex): bool
{
    $text = normalize_text($text);
    if ($text === '' || strlen($text) < 3) {
        return true;
    }

    if (in_array($text, $ignoreExact, true)) {
        return true;
    }

    if (str_contains($text, '{{') || str_contains($text, '@') || str_contains($text, '$')) {
        return true;
    }

    if (! preg_match('/[A-Za-z]/', $text)) {
        return true;
    }

    foreach ($ignoreRegex as $regex) {
        if (preg_match($regex, $text)) {
            return true;
        }
    }

    return false;
}

function module_for_path(string $relative): string
{
    $parts = explode('/', $relative);
    if (($parts[0] ?? '') === 'resources' && ($parts[1] ?? '') === 'views') {
        return $parts[2] ?? 'views';
    }
    if (($parts[0] ?? '') === 'app' && ($parts[1] ?? '') === 'Http' && ($parts[2] ?? '') === 'Controllers') {
        return strtolower($parts[3] ?? 'controllers');
    }
    if (($parts[0] ?? '') === 'resources' && ($parts[1] ?? '') === 'js') {
        return 'javascript';
    }
    if (($parts[0] ?? '') === 'public' && ($parts[1] ?? '') === 'js') {
        return 'javascript';
    }

    return $parts[0] ?? 'root';
}

function suggested_key(string $relative, string $text): string
{
    $module = module_for_path($relative);
    $base = strtolower($text);
    $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? $base;
    $base = trim($base, '_');
    $base = substr($base, 0, 50);

    $file = match (true) {
        str_contains($relative, 'reports/') => 'reports',
        str_contains($relative, 'billing/') => 'billing',
        str_contains($relative, 'patients/') => 'patients',
        str_contains($relative, 'visits/') => 'visits',
        str_contains($relative, 'settings/') => 'settings',
        str_contains($relative, 'users/') => 'users',
        str_contains($relative, 'invoices') => 'invoices',
        str_contains($relative, 'payments') => 'payments',
        default => in_array($module, ['components', 'layouts', 'partials'], true) ? 'common' : str_replace('-', '_', $module),
    };

    return "lang/{en,fr}/{$file}.php :: {$base}";
}

function audit_bucket(string $relative, string $text, string $context): string
{
    $normalized = normalize_text($text);
    $lowerPath = strtolower($relative);
    $lowerContext = strtolower($context);

    if (str_contains($lowerPath, '.bak') || str_contains($lowerPath, '/backup') || str_contains($lowerPath, '/backups')) {
        return 'backup_only_candidates';
    }

    if (
        str_starts_with($relative, 'lang/')
        || str_starts_with($relative, 'resources/lang/')
        || str_contains($lowerPath, '/lang/')
    ) {
        return 'language_file_candidates';
    }

    if (
        str_contains($lowerPath, '/demo')
        || str_contains($lowerPath, '/template')
        || str_contains($lowerPath, '/sample')
        || str_contains($lowerPath, 'resources/views/patterns/')
        || str_contains($lowerPath, 'resources/views/vendor/')
    ) {
        return 'demo_template_candidates';
    }

    if (str_starts_with($relative, 'app/Services/')) {
        return 'service_title_manual_review_candidates';
    }

    if (
        str_contains($relative, 'SidebarMenuBuilder.php')
        || str_contains($lowerContext, 'selectraw(')
        || str_contains($lowerContext, 'db::raw(')
        || str_contains($lowerContext, '->raw(')
        || str_contains($lowerContext, 'class=')
        || str_contains($lowerContext, 'queryselector')
        || str_contains($lowerContext, 'addeventlistener')
        || str_contains($lowerContext, '//')
        || str_starts_with(trim($context), '*')
        || in_array($normalized, ['UHMS', 'N/A', 'GHS', 'GH₵', 'GHâ‚µ'], true)
        || preg_match('/^(A|B|AB|O)[+-]$/', $normalized)
        || preg_match('/^(mg|ml|kg|bpm|mmHg|cm|L|%)$/i', $normalized)
    ) {
        return 'known_false_positive_candidates';
    }

    return 'active_runtime_candidates';
}

$findings = [];
$scanned = 0;

foreach ($files as $file) {
    $relative = relative_path($root, $file);
    $contents = @file($file);
    if ($contents === false) {
        continue;
    }
    $scanned++;

    foreach ($contents as $lineNumber => $line) {
        if (str_contains($line, '__(') || str_contains($line, '@lang') || str_contains($line, 'trans(')) {
            continue;
        }

        foreach ($patterns as $name => $pattern) {
            if (! preg_match_all($pattern['regex'], $line, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $text = normalize_text($match['text'] ?? $match['text2'] ?? '');
                if (should_ignore($text, $ignoreExact, $ignoreRegex)) {
                    continue;
                }

                $findings[] = [
                    'file' => $relative,
                    'line' => $lineNumber + 1,
                    'string' => $text,
                    'pattern' => $name,
                    'priority' => $pattern['priority'],
                    'module' => module_for_path($relative),
                    'context' => trim($line),
                    'recommendation' => 'Wrap in __() and add matching EN/FR keys if this is visible UI text.',
                    'suggested_key' => suggested_key($relative, $text),
                    'bucket' => audit_bucket($relative, $text, trim($line)),
                ];
            }
        }
    }
}

$byFile = [];
foreach ($findings as $finding) {
    $byFile[$finding['file']][] = $finding;
}

ksort($byFile);

$modules = [];
foreach ($findings as $finding) {
    $modules[$finding['module']] = ($modules[$finding['module']] ?? 0) + 1;
}
arsort($modules);

$bucketLabels = [
    'active_runtime_candidates' => 'Active runtime candidates',
    'demo_template_candidates' => 'Demo/template candidates',
    'backup_only_candidates' => 'Backup-only candidates',
    'language_file_candidates' => 'Language-file candidates',
    'known_false_positive_candidates' => 'Known false positives',
    'service_title_manual_review_candidates' => 'Service-title manual-review candidates',
];

$bucketCounts = array_fill_keys(array_keys($bucketLabels), 0);
foreach ($findings as $finding) {
    $bucketCounts[$finding['bucket']] = ($bucketCounts[$finding['bucket']] ?? 0) + 1;
}

$highFiles = [];
$mediumFiles = [];
foreach ($byFile as $file => $items) {
    $high = count(array_filter($items, fn ($item) => $item['priority'] === 'high'));
    $medium = count($items) - $high;
    if ($high > 0) {
        $highFiles[$file] = $high;
    } elseif ($medium > 0) {
        $mediumFiles[$file] = $medium;
    }
}
arsort($highFiles);
arsort($mediumFiles);

$report = [];
$report[] = '# UHMS Localisation Coverage Audit Report';
$report[] = '';
$report[] = 'Date: '.date('Y-m-d H:i:s P');
$report[] = '';
$report[] = '## Summary';
$report[] = '';
$report[] = '- Total files scanned: '.$scanned;
$report[] = '- Total files with possible hardcoded strings: '.count($byFile);
$report[] = '- Total hardcoded candidates found: '.count($findings);
$report[] = '- Modules affected: '.count($modules);
$report[] = '';
$report[] = '### Candidate Classification';
$report[] = '';
foreach ($bucketLabels as $bucket => $label) {
    $report[] = "- {$label}: ".($bucketCounts[$bucket] ?? 0);
}
$report[] = '';
$report[] = '### Modules Affected';
$report[] = '';
foreach (array_slice($modules, 0, 30, true) as $module => $count) {
    $report[] = "- {$module}: {$count}";
}
$report[] = '';
$report[] = '### High Priority Files';
$report[] = '';
foreach (array_slice($highFiles, 0, 30, true) as $file => $count) {
    $report[] = "- {$file}: {$count}";
}
$report[] = '';
$report[] = '### Medium Priority Files';
$report[] = '';
foreach (array_slice($mediumFiles, 0, 30, true) as $file => $count) {
    $report[] = "- {$file}: {$count}";
}
$report[] = '';
$report[] = '### Likely False Positives';
$report[] = '';
$report[] = '- Template/demo assets under vendor-published views or plugin-like frontend files may include sample copy.';
$report[] = '- Enum/model labels may be intentional canonical display names until each enum is wired to `statuses.php`.';
$report[] = '- Table cells containing fallback text from source data should be checked manually before translation.';
$report[] = '';
$report[] = '## Detailed Findings';
$report[] = '';
foreach ($byFile as $file => $items) {
    $report[] = '### `'.$file.'`';
    $report[] = '';
    foreach ($items as $item) {
        $context = str_replace('|', '\|', $item['context']);
        $report[] = '- Line '.$item['line'].' ['.$item['priority'].', '.$item['bucket'].']: `'.$item['string'].'`';
        $report[] = '  - Context: `'.$context.'`';
        $report[] = '  - Recommendation: '.$item['recommendation'];
        $report[] = '  - Suggested key: `'.$item['suggested_key'].'`';
    }
    $report[] = '';
}

$report[] = '## Coverage Status';
$report[] = '';
$report[] = '- Translated modules: modules with low/no high-priority findings after this audit and existing EN/FR lang files.';
$report[] = '- Partially translated modules: any module listed above with remaining candidates.';
$report[] = '- Untranslated modules: modules with many high-priority Blade findings and no dedicated language file.';
$report[] = '- Print/PDF coverage: inspect files containing `print` or `pdf` in Detailed Findings.';
$report[] = '- Email coverage: inspect `resources/views/emails` and `resources/views/mail` findings if present.';
$report[] = '- JavaScript coverage: inspect `resources/js` and `public/js` findings.';
$report[] = '- Controller flash message coverage: inspect `controller_flash` findings.';
$report[] = '- Dynamic enum/model label coverage: inspect `php_return_label` and `php_label_array` findings under `app/Enums` and `app/Models`.';
$report[] = '- Service title coverage: inspect `service_title_manual_review_candidates` and classify as user-facing, canonical stored title, internal code, SQL/internal expression, or translated downstream.';
$report[] = '';
$report[] = '## Cleanup Notes';
$report[] = '';
$report[] = '- This report is heuristic. Fix high-confidence visible UI strings first.';
$report[] = '- Do not translate user-entered names, clinical free text, route names, permission names, or internal codes.';

$reportPath = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'LOCALISATION_COVERAGE_AUDIT_REPORT.md';
file_put_contents($reportPath, implode(PHP_EOL, $report).PHP_EOL);

echo 'Report written: docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md'.PHP_EOL;
echo 'Files scanned: '.$scanned.PHP_EOL;
echo 'Files with candidates: '.count($byFile).PHP_EOL;
echo 'Candidates: '.count($findings).PHP_EOL;
foreach ($bucketLabels as $bucket => $label) {
    echo $label.': '.($bucketCounts[$bucket] ?? 0).PHP_EOL;
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Phase 14R.7 — read-only comparison of a wide-suite JUnit result against the
 * documented known-defect baseline.
 *
 * This is COMPARISON METADATA ONLY. It does not skip a test, does not convert a
 * failure into a pass, and does not influence PHPUnit's exit code. The raw
 * PHPUnit result remains the source of truth and is never hidden.
 */
class CompareWideBaselineCommand extends Command
{
    protected $signature = 'tests:compare-wide-baseline
        {--junit=storage/logs/phpunit-14r7-wide.xml : JUnit XML produced by the wide run}
        {--baseline=tests/Baselines/wide-suite-known-defects.json : Known-defect baseline}
        {--format=table : table|json}';

    protected $description = 'Compare a wide-suite JUnit result against the known-defect baseline (read-only).';

    public function handle(): int
    {
        $junitPath = (string) $this->option('junit');
        $baselinePath = (string) $this->option('baseline');

        if (! is_file($junitPath)) {
            $this->error('JUnit file not found: '.$junitPath);

            return self::FAILURE;
        }

        $observed = $this->parseJunit($junitPath);
        $baseline = is_file($baselinePath)
            ? (json_decode((string) file_get_contents($baselinePath), true)['known_defects'] ?? [])
            : [];

        $baselineIndex = [];
        foreach ($baseline as $entry) {
            $baselineIndex[$entry['test']] = $entry;
        }

        $rows = [];

        foreach ($observed['failures'] as $test => $status) {
            if (! isset($baselineIndex[$test])) {
                $rows[] = ['NEW_'.strtoupper($status), $test, '—'];

                continue;
            }

            $expected = $baselineIndex[$test]['expected_status'] ?? 'failure';
            $rows[] = $expected === $status
                ? ['KNOWN_UNCHANGED', $test, $baselineIndex[$test]['module'] ?? '—']
                : ['CHANGED_STATUS', $test, $expected.' → '.$status];
        }

        foreach ($baselineIndex as $test => $entry) {
            if (! isset($observed['failures'][$test])) {
                $rows[] = isset($observed['all'][$test])
                    ? ['KNOWN_RESOLVED', $test, $entry['module'] ?? '—']
                    : ['MISSING_TEST', $test, 'not present in this run'];
            }
        }

        $summary = [
            'totals' => $observed['totals'],
            'known_unchanged' => count(array_filter($rows, fn ($r) => $r[0] === 'KNOWN_UNCHANGED')),
            'known_resolved' => count(array_filter($rows, fn ($r) => $r[0] === 'KNOWN_RESOLVED')),
            'new_failures' => count(array_filter($rows, fn ($r) => str_starts_with($r[0], 'NEW_'))),
            'changed_status' => count(array_filter($rows, fn ($r) => $r[0] === 'CHANGED_STATUS')),
            'missing_tests' => count(array_filter($rows, fn ($r) => $r[0] === 'MISSING_TEST')),
        ];

        if ($this->option('format') === 'json') {
            $this->line(json_encode(['summary' => $summary, 'rows' => $rows],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return self::SUCCESS;
        }

        $this->table(['Classification', 'Test', 'Detail'], $rows ?: [['—', 'no differences', '—']]);
        $this->newLine();
        foreach ($summary['totals'] as $k => $v) {
            $this->line(sprintf('%-14s %s', $k.':', $v));
        }
        $this->newLine();
        foreach (['known_unchanged', 'known_resolved', 'new_failures', 'changed_status', 'missing_tests'] as $k) {
            $this->line(sprintf('%-18s %d', $k.':', $summary[$k]));
        }

        if ($summary['new_failures'] > 0) {
            $this->warn('New failures present — investigate. Do NOT add them to the baseline to pass the phase.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{totals: array<string,int>, failures: array<string,string>, all: array<string,bool>}
     */
    private function parseJunit(string $path): array
    {
        $xml = @simplexml_load_file($path);

        if (! $xml) {
            return ['totals' => [], 'failures' => [], 'all' => []];
        }

        $failures = [];
        $all = [];
        $totals = ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0];

        foreach ($xml->xpath('//testsuite[@name][not(testsuite)]') ?: [] as $suite) {
            $totals['tests'] += (int) $suite['tests'];
            $totals['assertions'] += (int) $suite['assertions'];
            $totals['failures'] += (int) $suite['failures'];
            $totals['errors'] += (int) $suite['errors'];
            $totals['skipped'] += (int) $suite['skipped'];
        }

        foreach ($xml->xpath('//testcase') ?: [] as $case) {
            $name = (string) $case['class'].'::'.(string) $case['name'];
            $all[$name] = true;

            if (isset($case->failure)) {
                $failures[$name] = 'failure';
            } elseif (isset($case->error)) {
                $failures[$name] = 'error';
            }
        }

        return ['totals' => $totals, 'failures' => $failures, 'all' => $all];
    }
}

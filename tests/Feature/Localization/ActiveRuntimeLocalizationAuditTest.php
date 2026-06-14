<?php

namespace Tests\Feature\Localization;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Phase 16 localisation gate: the localisation scanner must report zero
 * ACTIVE runtime candidates. Demo/template, known-false-positive,
 * service-title-manual-review and language-file candidates are NOT failures
 * but their counts are surfaced for developer awareness.
 */
class ActiveRuntimeLocalizationAuditTest extends TestCase
{
    private function runAudit(): string
    {
        $script = base_path('scripts/localisation-audit.php');
        $this->assertFileExists($script, 'localisation-audit.php scanner is missing.');

        $process = new Process([PHP_BINARY, $script], base_path());
        $process->setTimeout(300);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            "Scanner failed to run:\n".$process->getErrorOutput()
        );

        return $process->getOutput();
    }

    private function metric(string $output, string $label): ?int
    {
        if (preg_match('/'.preg_quote($label, '/').':\s*(\d+)/', $output, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    public function test_no_active_runtime_localisation_candidates(): void
    {
        $output = $this->runAudit();

        $active = $this->metric($output, 'Active runtime candidates');
        $this->assertNotNull($active, "Could not parse 'Active runtime candidates' from scanner output:\n{$output}");

        // Surface the non-blocking buckets so regressions there stay visible.
        $awareness = [];
        foreach ([
            'Demo/template candidates',
            'Known false positives',
            'Service-title manual-review candidates',
            'Language-file candidates',
        ] as $label) {
            $awareness[$label] = $this->metric($output, $label);
        }
        $summary = implode(', ', array_map(
            fn ($k, $v) => "{$k}={$v}",
            array_keys($awareness),
            array_values($awareness)
        ));

        $this->assertSame(
            0,
            $active,
            "Localisation regression: {$active} ACTIVE runtime candidate(s) detected (expected 0). ".
            "Wrap visible UI strings in __() / window.UHMS_I18N. Non-blocking buckets for awareness: {$summary}."
        );
    }
}

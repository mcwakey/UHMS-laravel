<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Logging Finalization: the funnel-aware `logs:audit` command. Uses isolated
 * controller/service fixtures (scanned via --scan-path) so the assertions don't
 * depend on the real app, and a temp baseline so the real one is never touched.
 * Pure static analysis — no DB.
 */
class LogsAuditCommandTest extends TestCase
{
    private string $dir = 'tests/fixtures/logs-audit';
    private string $baseline = 'tests/fixtures/logs-audit-baseline.json';

    protected function setUp(): void
    {
        parent::setUp();
        File::deleteDirectory(base_path($this->dir));
        File::ensureDirectoryExists(base_path($this->dir));
        @unlink(base_path($this->baseline));
        config(['logging_audit.baseline_path' => base_path($this->baseline)]);
        // Default fixture config (tests override per-case).
        config([
            'logging_audit.known_false_positive_controllers' => [],
            'logging_audit.covered_observers' => [],
            'logging_audit.backlog_controllers' => [],
            'logging_audit.backlog_actions' => [],
            'logging_audit.intentionally_skipped_actions' => [],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->dir));
        @unlink(base_path($this->baseline));
        parent::tearDown();
    }

    private function fixture(string $name, string $content): void
    {
        File::put(base_path($this->dir) . '/' . $name, $content);
    }

    private function controller(string $name, array $uses, array $methods, bool $inlineLog = false): void
    {
        $useLines = implode("\n", array_map(fn ($u) => "use App\\Services\\{$u};", $uses));
        $body = '';
        if ($inlineLog) {
            $body .= "    public function boot() { app(\\App\\Services\\ActivityLogService::class)->log('X','Y'); }\n";
        }
        foreach ($methods as $m) {
            $body .= "    public function {$m}() { return true; }\n";
        }
        $this->fixture($name, "<?php\nnamespace App\\Http\\Controllers\\Fixtures;\n{$useLines}\nclass " . pathinfo($name, PATHINFO_FILENAME) . "\n{\n{$body}}\n");
    }

    private function service(string $name, array $uses, bool $logs): void
    {
        $useLines = implode("\n", array_map(fn ($u) => "use App\\Services\\{$u};", $uses));
        $marker = $logs ? "    public function run() { app(\\App\\Services\\ActivityLogService::class)->log('a','b'); }\n" : "    public function run() { return 1; }\n";
        $this->fixture($name, "<?php\nnamespace App\\Services;\n{$useLines}\nclass " . pathinfo($name, PATHINFO_FILENAME) . "\n{\n{$marker}}\n");
    }

    /** @return array{0:int,1:array} */
    private function audit(array $opts = []): array
    {
        $opts = array_merge(['--scan-path' => $this->dir, '--json' => true], $opts);
        $code = Artisan::call('logs:audit', $opts);
        $json = json_decode(File::get(storage_path('reports/logs-audit-report.json')), true);

        return [$code, $json];
    }

    private function classOf(array $json, string $needle): ?string
    {
        foreach ($json['findings'] as $f) {
            if (str_contains($f['controller'], $needle)) {
                return $f['classification'];
            }
        }
        return null;
    }

    private function findingOf(array $json, string $needle): ?array
    {
        foreach ($json['findings'] as $f) {
            if (str_contains($f['controller'], $needle)) {
                return $f;
            }
        }
        return null;
    }

    public function test_controller_delegating_to_logging_service_is_covered(): void
    {
        $this->service('LoggedService.php', [], true);
        $this->controller('CoveredController.php', ['LoggedService'], ['store', 'update']);

        [, $json] = $this->audit();
        $this->assertSame('SERVICE_FUNNEL_COVERED', $this->classOf($json, 'CoveredController'));
    }

    public function test_transitive_funnel_is_detected(): void
    {
        // MidService doesn't log itself but delegates to LoggedService → covered.
        $this->service('LoggedService.php', [], true);
        $this->service('MidService.php', ['LoggedService'], false);
        $this->controller('TransitiveController.php', ['MidService'], ['approve']);

        [, $json] = $this->audit();
        $this->assertSame('SERVICE_FUNNEL_COVERED', $this->classOf($json, 'TransitiveController'));
        $this->assertGreaterThanOrEqual(2, $json['logging_services_detected']);
    }

    public function test_unverified_service_is_needs_review(): void
    {
        $this->service('PlainService.php', [], false);
        $this->controller('NeedsReviewController.php', ['PlainService'], ['store']);

        [, $json] = $this->audit();
        $this->assertSame('NEEDS_REVIEW', $this->classOf($json, 'NeedsReviewController'));
    }

    public function test_action_with_no_funnel_is_missing_log_and_does_not_count_covered(): void
    {
        $this->controller('GapController.php', [], ['destroy']);

        [, $json] = $this->audit();
        $finding = $this->findingOf($json, 'GapController');
        $this->assertSame('MISSING_LOG', $finding['classification']);
        $this->assertSame('CRITICAL', $finding['severity']); // destroy is critical
        $this->assertSame(1, $json['summary']['MISSING_LOG']);
        $this->assertSame(1, $json['real_missing_logs']);
    }

    public function test_backlog_and_skipped_classification_from_config(): void
    {
        $this->controller('BacklogController.php', [], ['store']);
        $this->controller('SkippedController.php', [], ['update']);
        config([
            'logging_audit.backlog_controllers' => [$this->dir . '/BacklogController.php'],
            'logging_audit.intentionally_skipped_actions' => [$this->dir . '/SkippedController.php@update'],
        ]);

        [, $json] = $this->audit();
        $this->assertSame('KNOWN_BACKLOG', $this->classOf($json, 'BacklogController'));
        $this->assertSame('INTENTIONALLY_SKIPPED', $this->classOf($json, 'SkippedController'));
        // Neither counts as a real missing log.
        $this->assertSame(0, $json['real_missing_logs']);
    }

    public function test_inline_logged_controller_is_not_flagged(): void
    {
        $this->controller('InlineController.php', [], ['store'], inlineLog: true);

        [, $json] = $this->audit();
        $this->assertNull($this->findingOf($json, 'InlineController'));
    }

    public function test_json_includes_classification_severity_and_summary(): void
    {
        $this->controller('GapController.php', [], ['merge']);

        [, $json] = $this->audit();
        $this->assertArrayHasKey('summary', $json);
        foreach (['MISSING_LOG', 'NEEDS_REVIEW', 'KNOWN_BACKLOG', 'INTENTIONALLY_SKIPPED', 'SERVICE_FUNNEL_COVERED'] as $k) {
            $this->assertArrayHasKey($k, $json['summary']);
        }
        $f = $this->findingOf($json, 'GapController');
        $this->assertArrayHasKey('classification', $f);
        $this->assertArrayHasKey('severity', $f);
        $this->assertArrayHasKey('reason', $f);
    }

    public function test_only_real_gaps_fail_mode(): void
    {
        $this->service('LoggedService.php', [], true);
        $this->controller('CoveredController.php', ['LoggedService'], ['store']);
        $this->controller('GapController.php', [], ['destroy']);

        // Real gap present → fails.
        [$code] = $this->audit(['--fail' => true, '--only-real-gaps' => true]);
        $this->assertNotSame(0, $code);

        // Remove the gap; only the covered controller remains → passes.
        @unlink(base_path($this->dir) . '/GapController.php');
        [$code2] = $this->audit(['--fail' => true, '--only-real-gaps' => true]);
        $this->assertSame(0, $code2);
    }

    public function test_min_severity_gate(): void
    {
        $this->controller('MediumController.php', [], ['store']); // MEDIUM, no funnel → MISSING_LOG

        [$codeCrit] = $this->audit(['--fail' => true, '--only-real-gaps' => true, '--min-severity' => 'CRITICAL']);
        $this->assertSame(0, $codeCrit, 'A MEDIUM gap must not trip a CRITICAL-only gate.');

        [$codeMed] = $this->audit(['--fail' => true, '--only-real-gaps' => true, '--min-severity' => 'MEDIUM']);
        $this->assertNotSame(0, $codeMed);
    }

    public function test_baseline_is_written_and_applied(): void
    {
        $this->service('LoggedService.php', [], true);
        $this->controller('CoveredController.php', ['LoggedService'], ['store']);
        $this->controller('GapController.php', [], ['destroy']); // CRITICAL MISSING — must NOT be baselined

        // Write baseline.
        Artisan::call('logs:audit', ['--scan-path' => $this->dir, '--write-baseline' => true]);
        $this->assertFileExists(base_path($this->baseline));

        [, $json] = $this->audit();
        $covered = $this->findingOf($json, 'CoveredController');
        $gap = $this->findingOf($json, 'GapController');
        $this->assertTrue($covered['in_baseline'], 'Covered finding should be accepted into the baseline.');
        $this->assertFalse($gap['in_baseline'], 'A CRITICAL real gap must never be baselined.');

        // Strict gate fails only on the non-baselined real gap.
        [$code] = $this->audit(['--fail' => true, '--strict' => true]);
        $this->assertNotSame(0, $code);
    }

    public function test_runs_on_real_project_advisory_by_default(): void
    {
        $code = Artisan::call('logs:audit');
        $this->assertSame(0, $code, 'Advisory mode must never fail the build.');
    }
}

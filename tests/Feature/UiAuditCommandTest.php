<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Phase 8 governance: the `ui:audit` command. Uses isolated Blade fixtures
 * (scanned via --path) so the assertions don't depend on the real views being
 * perfect. No DB needed — the command is pure static analysis.
 */
class UiAuditCommandTest extends TestCase
{
    private string $dir = 'tests/fixtures/ui-audit';

    protected function setUp(): void
    {
        parent::setUp();
        File::deleteDirectory(base_path($this->dir));
        File::ensureDirectoryExists(base_path($this->dir));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->dir));
        parent::tearDown();
    }

    private function fixture(string $name, string $content): void
    {
        File::put(base_path($this->dir) . '/' . $name, $content);
    }

    /** @return array{0:int,1:array} [exitCode, decoded json report] */
    private function audit(array $opts = []): array
    {
        $opts = array_merge(['--path' => $this->dir, '--json' => true], $opts);
        $code = Artisan::call('ui:audit', $opts);
        $json = json_decode(File::get(storage_path('reports/ui-audit-report.json')), true);

        return [$code, $json];
    }

    private function types(array $json): array
    {
        return array_values(array_unique(array_column($json['findings'], 'type')));
    }

    public function test_command_runs_successfully_on_full_project(): void
    {
        $code = Artisan::call('ui:audit');
        $this->assertSame(0, $code);
        $this->assertFileExists(storage_path('reports/ui-audit-report.md'));
    }

    public function test_detects_inline_workflow_badge(): void
    {
        $this->fixture('badge.blade.php',
            '<span class="badge bg-{{ $v->status->color() }}">{{ $v->status->label() }}</span>');

        [, $json] = $this->audit();
        $this->assertContains('inline-workflow-badge', $this->types($json));
    }

    public function test_detects_raw_sqlstate_as_critical(): void
    {
        $this->fixture('error.blade.php', '<div>Error: SQLSTATE[42000] Syntax error near...</div>');

        [, $json] = $this->audit();
        $crit = array_filter($json['findings'], fn ($f) => $f['type'] === 'raw-technical-error');
        $this->assertNotEmpty($crit);
        $this->assertSame('CRITICAL', array_values($crit)[0]['severity']);
    }

    public function test_detects_debug_output_in_view(): void
    {
        $this->fixture('debug.blade.php', '<div>@php dd($patient); @endphp</div>');

        [, $json] = $this->audit();
        $this->assertContains('debug-output-in-view', $this->types($json));
    }

    public function test_detects_icon_only_button_without_label(): void
    {
        $this->fixture('icon.blade.php', '<button class="btn btn-sm"><i class="ti ti-trash"></i></button>');

        [, $json] = $this->audit();
        $this->assertContains('icon-only-no-label', $this->types($json));
    }

    public function test_detects_missing_page_header(): void
    {
        $this->fixture('page.blade.php', <<<'BLADE'
        @extends('layouts.app')
        @section('content')
        <div class="d-flex justify-content-between border-bottom pb-2">
            <h3>Some Page</h3>
            <a class="btn btn-primary">New</a>
        </div>
        @endsection
        BLADE);

        [, $json] = $this->audit();
        $this->assertContains('missing-page-header', $this->types($json));
    }

    public function test_detects_foreign_framework(): void
    {
        $this->fixture('tw.blade.php', '<div class="flex"><!-- tailwind grid here --></div>');

        [, $json] = $this->audit();
        $this->assertContains('foreign-framework', $this->types($json));
    }

    public function test_writes_markdown_report(): void
    {
        $this->fixture('badge.blade.php', '<span class="badge bg-{{ $v->status->color() }}">x</span>');
        $this->audit();

        $md = File::get(storage_path('reports/ui-audit-report.md'));
        $this->assertStringContainsString('UHMS UI Audit Report', $md);
        $this->assertStringContainsString('Severity summary', $md);
    }

    public function test_json_flag_writes_json_report(): void
    {
        $this->fixture('badge.blade.php', '<span class="badge bg-{{ $v->status->color() }}">x</span>');
        [, $json] = $this->audit();

        $this->assertArrayHasKey('severity_counts', $json);
        $this->assertArrayHasKey('findings', $json);
        $this->assertFileExists(storage_path('reports/ui-audit-report.json'));
    }

    public function test_fail_returns_nonzero_on_new_critical(): void
    {
        $this->fixture('boom.blade.php', '<div>SQLSTATE[HY000] connection refused</div>');

        [$code] = $this->audit(['--fail' => true]);
        $this->assertNotSame(0, $code, 'A new critical finding outside the baseline must fail the build.');
    }

    public function test_min_severity_high_blocks_on_high_finding(): void
    {
        // An inline workflow badge is HIGH; --min-severity=HIGH must fail on it.
        $this->fixture('high.blade.php', '<span class="badge bg-{{ $v->status->color() }}">x</span>');

        [$code] = $this->audit(['--fail' => true, '--min-severity' => 'HIGH']);
        $this->assertNotSame(0, $code);

        // …but the default critical-only gate must NOT fail on a mere HIGH finding.
        [$code2] = $this->audit(['--fail' => true, '--min-severity' => 'CRITICAL']);
        $this->assertSame(0, $code2);
    }

    public function test_clean_fixtures_pass_fail_mode(): void
    {
        $this->fixture('clean.blade.php', '<div class="card"><div class="card-body">All good.</div></div>');

        [$code] = $this->audit(['--fail' => true]);
        $this->assertSame(0, $code);
    }

    public function test_does_not_flag_valid_status_badge(): void
    {
        $this->fixture('good.blade.php', <<<'BLADE'
        @extends('layouts.app')
        <x-page-header title="Visits" icon="ti-stethoscope" />
        <x-status-badge :status="$visit->status" domain="visit" />
        <x-empty-state icon="ti-inbox" title="None" message="No visits." />
        <x-data-table :headers="['A']" />
        BLADE);

        [, $json] = $this->audit();
        $types = $this->types($json);
        $this->assertNotContains('inline-workflow-badge', $types);
        $this->assertNotContains('raw-status-echo', $types);
        $this->assertNotContains('missing-page-header', $types);
        $this->assertNotContains('missing-empty-state', $types);
    }
}

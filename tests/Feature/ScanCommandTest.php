<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor;
use TechRaysLabs\DebtTracker\Tests\TestCase;

uses(TestCase::class);

it('runs scan command successfully', function () {
    $this->artisan('debt:scan')
        ->assertExitCode(0);
});

it('outputs grade in terminal', function () {
    $this->artisan('debt:scan')
        ->expectsOutputToContain('Project Grade');
});

it('exports markdown file when --export=markdown', function () {
    $exportPath = sys_get_temp_dir().'/DEBT_REPORT_TEST.md';

    config(['debt-tracker.export.path' => $exportPath]);

    $this->artisan('debt:scan --export=markdown')
        ->assertExitCode(0);

    expect(file_exists($exportPath))->toBeTrue();

    @unlink($exportPath);
});

it('summary command outputs grade line', function () {
    $this->artisan('debt:summary')
        ->expectsOutputToContain('Techrays Debt Tracker');
});

it('pushes result to Pulse when pulse.enabled is true', function (): void {
    $mock = $this->mock(DebtPulseIngestor::class);
    $mock->shouldReceive('push')->once();

    config(['debt-tracker.pulse.enabled' => true]);

    $this->artisan('debt:scan')->assertExitCode(0);
});

it('does not push to Pulse when pulse.enabled is false', function (): void {
    $mock = $this->mock(DebtPulseIngestor::class);
    $mock->shouldNotReceive('push');

    config(['debt-tracker.pulse.enabled' => false]);

    $this->artisan('debt:scan')->assertExitCode(0);
});

it('fails the scan gate when grade floor is breached', function () {
    // --fail-on-grade=A fails for any grade (every grade is A-or-worse)
    $this->artisan('debt:scan --fail-on-grade=A')
        ->assertExitCode(1);
});

it('passes the scan gate when thresholds are not breached', function () {
    // an enormous max-score is never exceeded → gate active and passes
    $this->artisan('debt:scan --max-score=1000000000')
        ->assertExitCode(0);
});

it('leaves the scan exit code unchanged when no gate flags are given', function () {
    $this->artisan('debt:scan')
        ->assertExitCode(0);
});

it('rejects an invalid fail-on-grade value with exit code 2', function () {
    $this->artisan('debt:scan --fail-on-grade=Z')
        ->assertExitCode(2);
});

it('rejects a non-numeric max-score with exit code 2', function () {
    $this->artisan('debt:scan --max-score=abc')
        ->assertExitCode(2);
});

it('activates the scan gate from config defaults', function () {
    config(['debt-tracker.ci.fail_on_grade' => 'A']);

    $this->artisan('debt:scan')
        ->assertExitCode(1);
});

it('lets a scan flag override a more lenient config default', function () {
    // Config alone would only fail on grade F; the testbench app is clean, so
    // config 'F' would pass. The stricter flag 'A' must win → exit 1.
    config(['debt-tracker.ci.fail_on_grade' => 'F']);

    $this->artisan('debt:scan --fail-on-grade=A')
        ->assertExitCode(1);
});

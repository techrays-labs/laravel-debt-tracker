<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
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

it('outputs only valid JSON for --format=agent', function () {
    Artisan::call('debt:scan', ['--format' => 'agent']);
    $output = trim(Artisan::output());

    $payload = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKeys(['schema_version', 'grade', 'total_score', 'items', 'priority', 'meta']);
});

it('returns the full schema with empty arrays for a zero-item scan in agent format', function () {
    Artisan::call('debt:scan', ['--format' => 'agent']);
    $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['items'])->toBe([])
        ->and($payload['priority'])->toBe([])
        ->and($payload['item_count'])->toBe(0);
});

it('still returns valid JSON (not error text) when the agent-format gate is breached', function () {
    $exitCode = Artisan::call('debt:scan', ['--format' => 'agent', '--fail-on-grade' => 'A']);
    $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(1)
        ->and($payload)->toHaveKey('schema_version')
        ->and($payload)->not->toHaveKey('error');
});

it('returns the AGT-4 error shape and exit 2 for invalid agent-format flag input', function () {
    $exitCode = Artisan::call('debt:scan', ['--format' => 'agent', '--fail-on-grade' => 'Z']);
    $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(2)
        ->and($payload)->toHaveKey('error')
        ->and($payload['error'])->toHaveKeys(['code', 'message']);
});

it('leaves non-agent output unchanged when --format is full or omitted', function () {
    $this->artisan('debt:scan')
        ->expectsOutputToContain('Project Grade')
        ->assertExitCode(0);
});

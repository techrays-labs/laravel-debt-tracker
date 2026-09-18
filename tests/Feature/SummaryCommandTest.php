<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use TechRaysLabs\DebtTracker\Tests\TestCase;

uses(TestCase::class);

it('runs the summary command and prints one line', function () {
    $this->artisan('debt:summary')
        ->expectsOutputToContain('[Techrays Debt Tracker] Grade:');
});

it('preserves legacy exit code 0 for a clean project when no gate flags are given', function () {
    // The default testbench app is essentially empty → grade A/B → exit 0.
    $this->artisan('debt:summary')
        ->assertExitCode(0);
});

it('fails the summary gate (exit 1) when a grade flag is breached', function () {
    // gate active; --fail-on-grade=A fails for any grade
    $this->artisan('debt:summary --fail-on-grade=A')
        ->assertExitCode(1);
});

it('passes the summary gate (exit 0) when an active threshold is not breached', function () {
    $this->artisan('debt:summary --max-score=1000000000')
        ->assertExitCode(0);
});

it('rejects an invalid summary fail-on-grade value with exit code 2', function () {
    $this->artisan('debt:summary --fail-on-grade=Z')
        ->assertExitCode(2);
});

it('rejects a non-numeric summary max-score with exit code 2', function () {
    $this->artisan('debt:summary --max-score=abc')
        ->assertExitCode(2);
});

it('activates the summary gate from config defaults', function () {
    // Config alone (no CLI flags) must activate the gate and override the
    // legacy grade-based exit-code scheme. --fail-on-grade=A fails for any grade.
    config(['debt-tracker.ci.fail_on_grade' => 'A']);

    $this->artisan('debt:summary')
        ->assertExitCode(1);
});

it('outputs only valid JSON for --format=agent, same schema as debt:scan', function () {
    Artisan::call('debt:summary', ['--format' => 'agent']);
    $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKeys(['schema_version', 'grade', 'total_score', 'items', 'priority', 'meta']);
});

it('returns the AGT-4 error shape and exit 2 for invalid agent-format flag input', function () {
    $exitCode = Artisan::call('debt:summary', ['--format' => 'agent', '--max-score' => 'abc']);
    $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(2)
        ->and($payload)->toHaveKey('error');
});

it('leaves the legacy one-line output unchanged when --format is omitted', function () {
    $this->artisan('debt:summary')
        ->expectsOutputToContain('[Techrays Debt Tracker] Grade:')
        ->assertExitCode(0);
});

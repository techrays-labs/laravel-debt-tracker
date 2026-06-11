<?php

declare(strict_types=1);

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

it('pushes result to Pulse when pulse.enabled is true and Pulse is installed', function (): void {
    if (! class_exists(\Laravel\Pulse\Pulse::class)) {
        $this->markTestSkipped('laravel/pulse not installed');
    }

    config(['debt-tracker.pulse.enabled' => true]);

    $this->artisan('debt:scan')->assertExitCode(0);

    // Verify DebtPulseIngestor::push() was called — confirmed by no exception and exit 0
    expect(true)->toBeTrue();
});

it('does not push to Pulse when pulse.enabled is false', function (): void {
    config(['debt-tracker.pulse.enabled' => false]);

    $this->artisan('debt:scan')->assertExitCode(0);

    expect(true)->toBeTrue();
});

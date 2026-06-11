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

it('pushes result to Pulse when pulse.enabled is true', function (): void {
    $mock = $this->mock(\TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor::class);
    $mock->shouldReceive('push')->once();

    config(['debt-tracker.pulse.enabled' => true]);

    $this->artisan('debt:scan')->assertExitCode(0);
});

it('does not push to Pulse when pulse.enabled is false', function (): void {
    $mock = $this->mock(\TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor::class);
    $mock->shouldNotReceive('push');

    config(['debt-tracker.pulse.enabled' => false]);

    $this->artisan('debt:scan')->assertExitCode(0);
});

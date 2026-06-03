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

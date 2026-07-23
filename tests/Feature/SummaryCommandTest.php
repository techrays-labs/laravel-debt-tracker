<?php

declare(strict_types=1);

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

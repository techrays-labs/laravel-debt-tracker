<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use TechRaysLabs\DebtTracker\DebtTrackerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [DebtTrackerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('debt-tracker.scan_paths', ['tests/Fixtures']);
    }
}

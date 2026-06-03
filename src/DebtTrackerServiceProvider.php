<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker;

use Illuminate\Support\ServiceProvider;
use TechRaysLabs\DebtTracker\Commands\ScanCommand;
use TechRaysLabs\DebtTracker\Commands\ShowClassCommand;
use TechRaysLabs\DebtTracker\Commands\ShowFileCommand;
use TechRaysLabs\DebtTracker\Commands\SummaryCommand;
use TechRaysLabs\DebtTracker\Reports\MarkdownReporter;

/**
 * Registers the DebtTracker package with the Laravel application.
 */
class DebtTrackerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/debt-tracker.php', 'debt-tracker');

        $this->app->singleton(DebtTracker::class, function ($app) {
            $config = $app['config']['debt-tracker'];
            $config['project_root'] = base_path();

            return new DebtTracker($config);
        });

        $this->app->singleton(MarkdownReporter::class, fn () => new MarkdownReporter);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/debt-tracker.php' => config_path('debt-tracker.php'),
            ], 'debt-tracker-config');

            $this->commands([
                ScanCommand::class,
                SummaryCommand::class,
                ShowFileCommand::class,
                ShowClassCommand::class,
            ]);
        }
    }
}

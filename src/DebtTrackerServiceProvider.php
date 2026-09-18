<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Pulse;
use Livewire\Component;
use Livewire\LivewireManager;
use TechRaysLabs\DebtTracker\Commands\McpServeCommand;
use TechRaysLabs\DebtTracker\Commands\ScanCommand;
use TechRaysLabs\DebtTracker\Commands\ShowClassCommand;
use TechRaysLabs\DebtTracker\Commands\ShowFileCommand;
use TechRaysLabs\DebtTracker\Commands\SummaryCommand;
use TechRaysLabs\DebtTracker\Pulse\Cards\DebtAuthorsCard;
use TechRaysLabs\DebtTracker\Pulse\Cards\DebtFilesCard;
use TechRaysLabs\DebtTracker\Pulse\Cards\DebtScoreCard;
use TechRaysLabs\DebtTracker\Pulse\Cards\DebtSummaryCard;
use TechRaysLabs\DebtTracker\Reports\JsonReporter;
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
        $this->app->singleton(JsonReporter::class, fn () => new JsonReporter);
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
                McpServeCommand::class,
            ]);
        }

        if (
            class_exists(Pulse::class) &&
            class_exists(Component::class)
        ) {
            $this->loadViewsFrom(__DIR__.'/../resources/views/pulse', 'debt-tracker-pulse');

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__.'/../resources/views/pulse' => resource_path('views/vendor/debt-tracker-pulse'),
                ], 'debt-tracker-pulse-views');
            }

            $this->callAfterResolving('livewire', function (LivewireManager $livewire): void {
                $livewire->component('debt-tracker-summary-card', DebtSummaryCard::class);
                $livewire->component('debt-tracker-score-card', DebtScoreCard::class);
                $livewire->component('debt-tracker-files-card', DebtFilesCard::class);
                $livewire->component('debt-tracker-authors-card', DebtAuthorsCard::class);
            });
        }
    }
}

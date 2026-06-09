<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Progress;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Reports\JsonReporter;
use TechRaysLabs\DebtTracker\Reports\MarkdownReporter;
use TechRaysLabs\DebtTracker\Reports\TerminalReporter;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;

/**
 * Primary scan command — runs all detectors and renders a full terminal report.
 */
class ScanCommand extends Command
{
    protected $signature = 'debt:scan
        {--only= : Comma-separated list of detectors to run (todos,complexity,coverage,dependencies,n1_queries,security,dead_code)}
        {--path= : Subdirectory to scan instead of configured scan_paths}
        {--export= : Export format: markdown, json, or markdown,json}
        {--min-score=0 : Minimum item score to include in output}
        {--format=full : Output format (full|compact)}';

    protected $description = 'Scan your Laravel application for technical debt';

    public function handle(DebtTracker $tracker, MarkdownReporter $markdownReporter, JsonReporter $jsonReporter): int
    {
        $only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : [];

        $paths = $this->option('path')
            ? [(string) $this->option('path')]
            : [];

        intro('Laravel Debt Tracker · by Techrays Labs');

        /** @var Progress|null $bar */
        $bar = null;

        $result = $tracker->scan(
            paths: $paths,
            onlyDetectors: $only,
            onProgress: function (int $current, int $total, string $filePath) use (&$bar): void {
                if ($bar === null) {
                    $bar = progress(label: 'Scanning files', steps: $total);
                    $bar->start();
                }

                $bar->label(basename($filePath));
                $bar->advance();

                if ($current === $total) {
                    $bar->finish();
                }
            },
        );

        $reporter = new TerminalReporter($this->output);
        $reporter->render($result);

        $exportFormats = $this->option('export')
            ? array_map('trim', explode(',', (string) $this->option('export')))
            : [];

        if (in_array('markdown', $exportFormats, true)) {
            $exportPath = config('debt-tracker.export.path', base_path('DEBT_REPORT.md'));
            $markdownReporter->writeToFile($result, $exportPath);
            note("Markdown report saved to: {$exportPath}");
        }

        if (in_array('json', $exportFormats, true)) {
            $jsonPath = config('debt-tracker.export.json_path', base_path('DEBT_REPORT.json'));
            $jsonReporter->writeToFile($result, $jsonPath);
            note("JSON report saved to: {$jsonPath}");
        }

        outro("Scan complete · Grade: {$result->grade} · Score: {$result->totalScore} · {$result->totalItems()} items found");

        return self::SUCCESS;
    }
}

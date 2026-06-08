<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Reports\JsonReporter;
use TechRaysLabs\DebtTracker\Reports\MarkdownReporter;
use TechRaysLabs\DebtTracker\Reports\TerminalReporter;

/**
 * Primary scan command — runs all detectors and renders a full terminal report.
 */
class ScanCommand extends Command
{
    protected $signature = 'debt:scan
        {--only= : Comma-separated list of detectors to run (todos,complexity,coverage,dependencies,n1_queries)}
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

        $this->info('Scanning for technical debt...');

        $result = $tracker->scan(paths: $paths, onlyDetectors: $only);

        $reporter = new TerminalReporter($this->output);
        $reporter->render($result);

        $exportFormats = $this->option('export')
            ? array_map('trim', explode(',', (string) $this->option('export')))
            : [];

        if (in_array('markdown', $exportFormats, true)) {
            $exportPath = config('debt-tracker.export.path', base_path('DEBT_REPORT.md'));
            $markdownReporter->writeToFile($result, $exportPath);
            $this->info("Markdown report saved to: {$exportPath}");
        }

        if (in_array('json', $exportFormats, true)) {
            $jsonPath = config('debt-tracker.export.json_path', base_path('DEBT_REPORT.json'));
            $jsonReporter->writeToFile($result, $jsonPath);
            $this->info("JSON report saved to: {$jsonPath}");
        }

        return self::SUCCESS;
    }
}

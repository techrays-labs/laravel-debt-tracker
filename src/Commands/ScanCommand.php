<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Progress;
use TechRaysLabs\DebtTracker\Agent\AgentFormatSerializer;
use TechRaysLabs\DebtTracker\Agent\RendersAgentFormat;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Gating\DebtGate;
use TechRaysLabs\DebtTracker\Gating\ResolvesGateOptions;
use TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor;
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
    use RendersAgentFormat;
    use ResolvesGateOptions;

    protected $signature = 'debt:scan
        {--only= : Comma-separated list of detectors to run (todos,complexity,coverage,dependencies,n1_queries,security,dead_code)}
        {--path= : Subdirectory to scan instead of configured scan_paths}
        {--export= : Export format: markdown, json, or markdown,json}
        {--min-score=0 : Minimum item score to include in output}
        {--format=full : Output format (full|compact|agent)}
        {--limit=10 : Caps the "priority" array size in --format=agent}
        {--fail-on-grade= : Fail (exit 1) when the grade is this letter or worse (A-F)}
        {--max-score= : Fail (exit 1) when the total debt score exceeds this number}';

    protected $description = 'Scan your Laravel application for technical debt';

    public function handle(
        DebtTracker $tracker,
        MarkdownReporter $markdownReporter,
        JsonReporter $jsonReporter,
        DebtGate $gate,
        AgentFormatSerializer $serializer,
    ): int {
        $isAgentFormat = $this->isAgentFormat();

        try {
            [$failOnGrade, $maxScore] = $this->resolveGateThresholds();
        } catch (\InvalidArgumentException $e) {
            if ($isAgentFormat) {
                $this->renderAgentError($serializer, 'INVALID_OPTION', $e->getMessage());

                return self::INVALID;
            }

            $this->components->error($e->getMessage());

            return self::INVALID;
        }

        $only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : [];

        $paths = $this->option('path')
            ? [(string) $this->option('path')]
            : [];

        if ($isAgentFormat) {
            try {
                $result = $tracker->scan(paths: $paths, onlyDetectors: $only);
            } catch (\Throwable $e) {
                $this->renderAgentError($serializer, 'SCAN_FAILED', $e->getMessage());

                return 3;
            }
        } else {
            intro('Laravel Debt Tracker · by Techrays Labs');

            /** @var Progress<int>|null $bar */
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
        }

        $exportFormats = $this->option('export')
            ? array_map('trim', explode(',', (string) $this->option('export')))
            : [];

        if (in_array('markdown', $exportFormats, true)) {
            $exportPath = config('debt-tracker.export.path', base_path('DEBT_REPORT.md'));
            $markdownReporter->writeToFile($result, $exportPath);

            if (! $isAgentFormat) {
                note("Markdown report saved to: {$exportPath}");
            }
        }

        if (in_array('json', $exportFormats, true)) {
            $jsonPath = config('debt-tracker.export.json_path', base_path('DEBT_REPORT.json'));
            $jsonReporter->writeToFile($result, $jsonPath);

            if (! $isAgentFormat) {
                note("JSON report saved to: {$jsonPath}");
            }
        }

        if (! $isAgentFormat) {
            outro("Scan complete · Grade: {$result->grade} · Score: {$result->totalScore} · {$result->totalItems()} items found");
        }

        if (config('debt-tracker.pulse.enabled', true)) {
            try {
                app(DebtPulseIngestor::class)->push($result);
            } catch (\Throwable $e) {
                if (! $isAgentFormat) {
                    $this->components->warn("Pulse push failed: {$e->getMessage()}");
                }
            }
        }

        $gateResult = $gate->evaluate($result, $failOnGrade, $maxScore);

        if ($isAgentFormat) {
            $this->renderAgentPayload($serializer, $result, (int) $this->option('limit'));

            return ($gateResult->active && ! $gateResult->passed) ? self::FAILURE : self::SUCCESS;
        }

        if ($gateResult->active && ! $gateResult->passed) {
            foreach ($gateResult->reasons as $reason) {
                $this->components->error("Debt gate failed: {$reason}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

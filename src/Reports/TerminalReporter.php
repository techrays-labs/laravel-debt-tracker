<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Reports;

use Symfony\Component\Console\Output\OutputInterface;
use TechRaysLabs\DebtTracker\Scoring\GradeResolver;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Renders a formatted scan report to the terminal.
 */
class TerminalReporter
{
    private GradeResolver $gradeResolver;

    public function __construct(private readonly OutputInterface $output)
    {
        $this->gradeResolver = new GradeResolver;
    }

    /**
     * Renders the full scan report to the output.
     */
    public function render(ScanResult $result): void
    {
        $this->renderSummary($result);
        $this->renderCategoryTable($result->byCategory);
        $this->renderFilesTable($result->topFiles());
        $this->renderClassesTable($result->topClasses());
        $this->renderAuthorsTable($result);
        $this->output->writeln('');
        $this->output->writeln('  <comment>Run with --export=markdown or --export=json to save the full report.</comment>');
        $this->output->writeln('');
    }

    private function renderHeader(): void
    {
        $this->output->writeln('');
        $this->output->writeln('  <fg=cyan>╔══════════════════════════════════════════════════════════╗</>');
        $this->output->writeln('  <fg=cyan>║        Laravel Debt Tracker · by Techrays Labs           ║</>');
        $this->output->writeln('  <fg=cyan>║  https://github.com/techrays-labs/laravel-debt-tracker   ║</>');
        $this->output->writeln('  <fg=cyan>╚══════════════════════════════════════════════════════════╝</>');
        $this->output->writeln('');
    }

    private function renderSummary(ScanResult $result): void
    {
        $color = $this->gradeColor($result->grade);
        $hours = number_format($result->estimatedHours, 1);
        $files = count($result->fileResults);
        $items = $result->totalItems();

        $this->output->writeln(
            "  Project Grade: <fg={$color};options=bold>{$result->grade}</>"
            ."    Total Score: <options=bold>{$result->totalScore}</>"
            ."    Est. Hours: <options=bold>{$hours}h</>"
            ."    Files: <options=bold>{$files}</>"
            ."    Items: <options=bold>{$items}</>"
        );
        $this->output->writeln('');
    }

    /** @param array<string, int> $byCategory */
    private function renderCategoryTable(array $byCategory): void
    {
        $labels = [
            'todo'       => 'TODOs / FIXMEs',
            'complexity' => 'Complexity',
            'coverage'   => 'Missing Test Coverage',
            'dependency' => 'Outdated Dependencies',
            'n1_queries' => 'N+1 Queries',
            'security'   => 'Security Smells',
            'dead_code'  => 'Dead Code',
        ];

        // Merge known categories (with 0 defaults) over actual results so all
        // detectors are always visible, even when they find nothing.
        $rows = array_merge(array_fill_keys(array_keys($labels), 0), $byCategory);

        $this->output->writeln('  <options=bold>Debt by Category:</>');
        $this->output->writeln('  ┌─────────────────────────┬───────┬──────────┐');
        $this->output->writeln('  │ Category                │ Items │ Score    │');
        $this->output->writeln('  ├─────────────────────────┼───────┼──────────┤');

        foreach ($rows as $type => $score) {
            $label = $labels[$type] ?? ucfirst($type);
            $this->output->writeln(sprintf(
                '  │ %-23s │  ---  │  %-6d  │',
                $label,
                $score
            ));
        }

        $this->output->writeln('  └─────────────────────────┴───────┴──────────┘');
        $this->output->writeln('');
    }

    /** @param \TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult[] $files */
    private function renderFilesTable(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $this->output->writeln('  <options=bold>Top 10 Worst Files:</>');
        $this->output->writeln('  ┌──────────────────────────────────────────┬───────┬───────┐');
        $this->output->writeln('  │ File                                     │ Items │ Score │');
        $this->output->writeln('  ├──────────────────────────────────────────┼───────┼───────┤');

        foreach ($files as $file) {
            $path = strlen($file->relativePath) > 40
                ? '...'.substr($file->relativePath, -37)
                : $file->relativePath;

            $this->output->writeln(sprintf(
                '  │ %-40s │  %-4d │  %-4d │',
                $path,
                $file->itemCount,
                $file->totalScore
            ));
        }

        $this->output->writeln('  └──────────────────────────────────────────┴───────┴───────┘');
        $this->output->writeln('');
    }

    /** @param \TechRaysLabs\DebtTracker\ValueObjects\ClassDebtResult[] $classes */
    private function renderClassesTable(array $classes): void
    {
        if (empty($classes)) {
            return;
        }

        $this->output->writeln('  <options=bold>Top 10 Worst Classes:</>');
        $this->output->writeln('  ┌──────────────────────────────────────────┬───────┬───────┐');
        $this->output->writeln('  │ Class                                    │ Items │ Score │');
        $this->output->writeln('  ├──────────────────────────────────────────┼───────┼───────┤');

        foreach ($classes as $class) {
            $fqn = strlen($class->fullyQualifiedName) > 40
                ? '...'.substr($class->fullyQualifiedName, -37)
                : $class->fullyQualifiedName;

            $this->output->writeln(sprintf(
                '  │ %-40s │  %-4d │  %-4d │',
                $fqn,
                $class->itemCount,
                $class->totalScore
            ));
        }

        $this->output->writeln('  └──────────────────────────────────────────┴───────┴───────┘');
        $this->output->writeln('');
    }

    private function renderAuthorsTable(ScanResult $result): void
    {
        $authors = $result->topAuthors(10);

        // Only render if at least one named author (not just 'Unknown')
        $knownAuthors = array_filter(
            $authors,
            static fn ($score, $author) => $author !== 'Unknown',
            ARRAY_FILTER_USE_BOTH,
        );

        if (empty($knownAuthors)) {
            return;
        }

        $this->output->writeln('  <options=bold>Top Debt Authors:</>');
        $this->output->writeln('  ┌──────────────────────────┬────────────┐');
        $this->output->writeln('  │ Author                   │ Debt Score │');
        $this->output->writeln('  ├──────────────────────────┼────────────┤');

        foreach ($authors as $author => $score) {
            $display = strlen($author) > 24 ? substr($author, 0, 21).'...' : $author;
            $this->output->writeln(sprintf('  │ %-24s │ %10d │', $display, $score));
        }

        $this->output->writeln('  └──────────────────────────┴────────────┘');
        $this->output->writeln('  <comment>Higher score = more debt attributed to this author.</comment>');
        $this->output->writeln('');
    }

    private function gradeColor(string $grade): string
    {
        return $this->gradeResolver->color($grade);
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Reports;

use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Generates a Markdown debt report and writes it to a file.
 */
class MarkdownReporter
{
    /**
     * Generates the full Markdown report string.
     */
    public function generate(ScanResult $result): string
    {
        return implode("\n", [
            $this->renderHeader($result),
            $this->renderExecutiveSummary($result),
            $this->renderCategoryTable($result),
            $this->renderTopFiles($result),
            $this->renderTopClasses($result),
            $this->renderFullBreakdown($result),
            $this->renderFooter(),
        ]);
    }

    /**
     * Writes the generated Markdown to the given file path.
     */
    public function writeToFile(ScanResult $result, string $path): void
    {
        file_put_contents($path, $this->generate($result));
    }

    private function renderHeader(ScanResult $result): string
    {
        $badge = $this->gradeBadge($result->grade);
        $timestamp = $result->generatedAt->format('Y-m-d H:i:s');
        $hours = number_format($result->estimatedHours, 1);

        return <<<MD
        # Technical Debt Report

        {$badge}

        | Field         | Value                     |
        |---------------|---------------------------|
        | Generated     | {$timestamp}              |
        | Project Grade | **{$result->grade}**      |
        | Total Score   | {$result->totalScore}     |
        | Est. Hours    | {$hours}h                 |
        | Files Scanned | {$result->totalItems()}   |

        MD;
    }

    private function renderExecutiveSummary(ScanResult $result): string
    {
        $grade = $result->grade;
        $score = $result->totalScore;
        $hours = number_format($result->estimatedHours, 1);
        $topFile = $result->topFiles(1)[0] ?? null;
        $topClass = $result->topClasses(1)[0] ?? null;

        $gradeDesc = match ($grade) {
            'A' => 'Your codebase is in excellent health with minimal technical debt.',
            'B' => 'Your codebase is in good health with manageable technical debt.',
            'C' => 'Your codebase has concerning levels of technical debt that should be addressed.',
            'D' => 'Your codebase has critical levels of technical debt requiring immediate attention.',
            default => 'Your codebase is in an emergency state with extreme levels of technical debt.',
        };

        $lines = [
            '## Executive Summary',
            '',
            $gradeDesc,
            "The project scored **{$score}** total debt points, estimated at approximately **{$hours} developer hours** to remediate.",
        ];

        if ($topFile !== null) {
            $lines[] = "The most indebted file is `{$topFile->relativePath}` with a score of {$topFile->totalScore}.";
        }

        if ($topClass !== null) {
            $lines[] = "The most indebted class is `{$topClass->className}` with a score of {$topClass->totalScore}.";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderCategoryTable(ScanResult $result): string
    {
        $lines = [
            '## Debt by Category',
            '',
            '| Category | Score |',
            '|----------|-------|',
        ];

        $labels = [
            'todo' => 'TODOs / FIXMEs',
            'complexity' => 'Complexity',
            'coverage' => 'Missing Test Coverage',
            'dependency' => 'Outdated Dependencies',
        ];

        foreach ($result->byCategory as $type => $score) {
            $label = $labels[$type] ?? ucfirst($type);
            $lines[] = "| {$label} | {$score} |";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderTopFiles(ScanResult $result): string
    {
        $lines = [
            '## Top 10 Worst Files',
            '',
            '| File | Items | Score |',
            '|------|-------|-------|',
        ];

        foreach ($result->topFiles(10) as $file) {
            $lines[] = "| `{$file->relativePath}` | {$file->itemCount} | {$file->totalScore} |";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderTopClasses(ScanResult $result): string
    {
        $lines = [
            '## Top 10 Worst Classes',
            '',
            '| Class | Items | Score |',
            '|-------|-------|-------|',
        ];

        foreach ($result->topClasses(10) as $class) {
            $lines[] = "| `{$class->fullyQualifiedName}` | {$class->itemCount} | {$class->totalScore} |";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderFullBreakdown(ScanResult $result): string
    {
        $lines = ['## Full File Breakdown', ''];

        foreach ($result->fileResults as $file) {
            if ($file->itemCount === 0) {
                continue;
            }

            $lines[] = '<details>';
            $lines[] = "<summary><strong>{$file->relativePath}</strong> — Score: {$file->totalScore}, Items: {$file->itemCount}</summary>";
            $lines[] = '';
            $lines[] = '| Line | Type | Description | Score |';
            $lines[] = '|------|------|-------------|-------|';

            foreach ($file->items as $item) {
                $desc = addslashes(substr($item->description, 0, 100));
                $lines[] = "| {$item->lineNumber} | {$item->type} | {$desc} | {$item->finalScore()} |";
            }

            $lines[] = '';
            $lines[] = '</details>';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function renderFooter(): string
    {
        return <<<'MD'

        ---

        *Generated by [Laravel Debt Tracker](https://github.com/techrays-labs/laravel-debt-tracker) · by [Techrays Labs](https://techrayslabs.com)*
        MD;
    }

    private function gradeBadge(string $grade): string
    {
        $color = match ($grade) {
            'A' => 'brightgreen',
            'B' => 'green',
            'C' => 'yellow',
            'D' => 'orange',
            default => 'red',
        };

        $encoded = urlencode('Debt Grade');

        return "![Debt Grade](https://img.shields.io/badge/{$encoded}-{$grade}-{$color})";
    }
}

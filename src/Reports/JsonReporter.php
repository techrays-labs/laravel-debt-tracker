<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Reports;

use TechRaysLabs\DebtTracker\Exceptions\ScanFailedException;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Generates a machine-readable JSON debt report.
 */
class JsonReporter
{
    /**
     * Generates the full report as a pretty-printed JSON string.
     */
    public function generate(ScanResult $result): string
    {
        $allItems = [];
        foreach ($result->fileResults as $f) {
            $allItems = array_merge($allItems, $f->items);
        }

        $itemsByCategory = [];
        foreach ($allItems as $item) {
            $itemsByCategory[$item->type] = ($itemsByCategory[$item->type] ?? 0) + 1;
        }

        $byCategoryData = [];
        foreach ($result->byCategory as $cat => $score) {
            $byCategoryData[] = [
                'category' => $cat,
                'items' => $itemsByCategory[$cat] ?? 0,
                'score' => $score,
            ];
        }

        $authorData = [];

        foreach ($result->topAuthors(10) as $author => $score) {
            $authorData[] = ['author' => $author, 'debt_score' => $score];
        }

        $data = [
            'generated_at' => $result->generatedAt->format(\DateTimeInterface::ATOM),
            'grade' => $result->grade,
            'total_score' => $result->totalScore,
            'estimated_hours' => round($result->estimatedHours, 1),
            'file_count' => count($result->fileResults),
            'item_count' => $result->totalItems(),
            'by_category' => $byCategoryData,
            'authors'     => $authorData,
            'top_files' => array_map(
                static fn ($f) => [
                    'path' => $f->relativePath,
                    'items' => $f->itemCount,
                    'score' => $f->totalScore,
                ],
                $result->topFiles(10),
            ),
            'top_classes' => array_map(
                static fn ($c) => [
                    'class' => $c->className,
                    'fqn' => $c->fullyQualifiedName,
                    'items' => $c->itemCount,
                    'score' => $c->totalScore,
                ],
                $result->topClasses(10),
            ),
            'items' => array_map(
                static fn ($item) => [
                    'type' => $item->type,
                    'file' => $item->filePath,
                    'line' => $item->lineNumber,
                    'description' => $item->description,
                    'base_score' => $item->baseScore,
                    'age_days' => $item->ageDays,
                    'age_band' => $item->ageBand,
                    'age_multiplier' => $item->ageMultiplier,
                    'final_score' => $item->finalScore(),
                    'author' => $item->gitAuthor,
                ],
                $allItems,
            ),
            'meta' => [
                'package' => 'techrays-labs/laravel-debt-tracker',
                'url' => 'https://github.com/techrays-labs/laravel-debt-tracker',
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Writes the JSON report to the given file path.
     *
     * @throws ScanFailedException
     */
    public function writeToFile(ScanResult $result, string $path): void
    {
        set_error_handler(function () {
            // Suppress errors
        });

        try {
            $written = file_put_contents($path, $this->generate($result));
        } finally {
            restore_error_handler();
        }

        if ($written === false) {
            throw new ScanFailedException("Could not write JSON report to: {$path}");
        }
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Analyzers;

use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\Scoring\ScoreCalculator;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;

/**
 * Coordinates all enabled detectors against a single file.
 */
class FileAnalyzer
{
    /**
     * @param  DetectorInterface[]  $detectors
     */
    public function __construct(
        private readonly array $detectors,
        private readonly AstParser $astParser,
        private readonly GitBlameReader $gitReader,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly string $projectRoot = '',
    ) {}

    /**
     * Runs all detectors on the given file and aggregates results.
     */
    public function analyze(string $filePath): FileDebtResult
    {
        $ast = $this->astParser->parse($filePath);

        $context = [
            'ast' => $ast,
            'astParser' => $this->astParser,
            'git' => $this->gitReader,
        ];

        $allItems = [];

        foreach ($this->detectors as $detector) {
            if (! $detector->isEnabled()) {
                continue;
            }

            try {
                $items = $detector->detect($filePath, $context);
                $allItems = array_merge($allItems, $items);
            } catch (\Throwable) {
                // Never let a detector crash the entire scan.
            }
        }

        $totalScore = $this->scoreCalculator->calculate($allItems);
        $relativePath = $this->toRelative($filePath);

        return new FileDebtResult(
            filePath: $filePath,
            relativePath: $relativePath,
            items: $allItems,
            totalScore: $totalScore,
            itemCount: count($allItems),
        );
    }

    private function toRelative(string $absolutePath): string
    {
        $root = rtrim($this->projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if ($this->projectRoot !== '' && str_starts_with($absolutePath, $root)) {
            return substr($absolutePath, strlen($root));
        }

        return $absolutePath;
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\ValueObjects;

/**
 * Aggregated debt results for a single file.
 */
final class FileDebtResult
{
    /**
     * @param  string  $filePath  Absolute path to the file
     * @param  string  $relativePath  Path relative to the project root
     * @param  DebtItem[]  $items  All debt items found in this file
     * @param  int  $totalScore  Sum of all item final scores
     * @param  int  $itemCount  Number of debt items detected
     */
    public function __construct(
        public readonly string $filePath,
        public readonly string $relativePath,
        public readonly array $items,
        public readonly int $totalScore,
        public readonly int $itemCount,
    ) {}

    /**
     * Returns the N highest-scoring debt items, sorted descending.
     *
     * @return DebtItem[]
     */
    public function topItems(int $n = 5): array
    {
        $sorted = $this->items;

        usort($sorted, static fn (DebtItem $a, DebtItem $b) => $b->finalScore() <=> $a->finalScore());

        return array_slice($sorted, 0, $n);
    }
}

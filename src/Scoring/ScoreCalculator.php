<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Scoring;

use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Aggregates debt item scores into totals and category breakdowns.
 */
class ScoreCalculator
{
    /**
     * Returns the sum of all finalScore() values.
     *
     * @param  DebtItem[]  $debtItems
     */
    public function calculate(array $debtItems): int
    {
        return array_sum(array_map(static fn (DebtItem $i) => $i->finalScore(), $debtItems));
    }

    /**
     * Returns per-category score totals.
     *
     * @param  DebtItem[]  $debtItems
     * @return array<string, int>
     */
    public function byCategory(array $debtItems): array
    {
        $categories = [];

        foreach ($debtItems as $item) {
            $categories[$item->type] = ($categories[$item->type] ?? 0) + $item->finalScore();
        }

        return $categories;
    }
}

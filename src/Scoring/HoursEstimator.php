<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Scoring;

/**
 * Estimates remediation effort in hours (and optionally cost) from a debt score.
 */
class HoursEstimator
{
    /**
     * Converts a debt score to estimated developer hours.
     */
    public function estimate(int $totalScore, float $hoursPerPoint = 0.25): float
    {
        return round($totalScore * $hoursPerPoint, 2);
    }

    /**
     * Returns the estimated dollar cost, or null when no hourly rate is configured.
     */
    public function estimateCost(float $hours, ?int $hourlyRate): ?float
    {
        if ($hourlyRate === null) {
            return null;
        }

        return round($hours * $hourlyRate, 2);
    }
}

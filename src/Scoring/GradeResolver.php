<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Scoring;

/**
 * Maps a total debt score to a letter grade and terminal colour.
 */
class GradeResolver
{
    private const THRESHOLDS = [
        'A' => [0, 100],
        'B' => [101, 300],
        'C' => [301, 600],
        'D' => [601, 1000],
        'F' => [1001, PHP_INT_MAX],
    ];

    /**
     * Returns the letter grade for the given total score.
     */
    public function resolve(int $totalScore): string
    {
        foreach (self::THRESHOLDS as $grade => [$min, $max]) {
            if ($totalScore >= $min && $totalScore <= $max) {
                return $grade;
            }
        }

        return 'F';
    }

    /**
     * Returns the Symfony Console colour tag name for a grade.
     */
    public function color(string $grade): string
    {
        return match ($grade) {
            'A' => 'green',
            'B' => 'cyan',
            'C' => 'yellow',
            'D' => 'red',
            default => 'red',
        };
    }
}

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
     * Returns a severity rank for the grade where a worse grade yields a
     * higher number (A = 1 … F = 5). Enables "grade is threshold-or-worse"
     * comparisons via rank(actual) >= rank(threshold).
     *
     * @throws \InvalidArgumentException when the grade letter is unknown
     */
    public function rank(string $grade): int
    {
        return match ($grade) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'F' => 5,
            default => throw new \InvalidArgumentException("Unknown grade: {$grade}"),
        };
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

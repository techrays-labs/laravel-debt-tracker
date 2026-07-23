<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Gating;

use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Shared logic for console commands that expose the CI debt gate.
 *
 * Resolves the effective thresholds using precedence: CLI flag → config default
 * → off (null). Validates flag input and normalizes the grade letter.
 *
 * @mixin Command
 */
trait ResolvesGateOptions
{
    private const VALID_GRADES = ['A', 'B', 'C', 'D', 'F'];

    /**
     * Resolve the effective (failOnGrade, maxScore) thresholds.
     *
     * @return array{0: string|null, 1: int|null}
     *
     * @throws InvalidArgumentException when a provided flag value is invalid
     */
    protected function resolveGateThresholds(): array
    {
        return [$this->resolveFailOnGrade(), $this->resolveMaxScore()];
    }

    /**
     * Resolve the effective --fail-on-grade value from the CLI flag or config default.
     */
    private function resolveFailOnGrade(): ?string
    {
        $raw = $this->option('fail-on-grade') ?? config('debt-tracker.ci.fail_on_grade');

        if ($raw === null || $raw === '') {
            return null;
        }

        $grade = strtoupper(trim((string) $raw));

        if (! in_array($grade, self::VALID_GRADES, true)) {
            throw new InvalidArgumentException(
                "Invalid --fail-on-grade value '{$raw}'. Use one of: A, B, C, D, F."
            );
        }

        return $grade;
    }

    /**
     * Resolve the effective --max-score value from the CLI flag or config default.
     */
    private function resolveMaxScore(): ?int
    {
        $raw = $this->option('max-score') ?? config('debt-tracker.ci.max_score');

        if ($raw === null || $raw === '') {
            return null;
        }

        if (! ctype_digit((string) $raw)) {
            throw new InvalidArgumentException(
                "Invalid --max-score value '{$raw}'. Use a non-negative integer."
            );
        }

        return (int) $raw;
    }
}

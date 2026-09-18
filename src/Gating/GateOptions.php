<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Gating;

use InvalidArgumentException;

/**
 * Pure validation/normalization for CI gate threshold values.
 *
 * Shared by console commands (via ResolvesGateOptions, which layers
 * Command::option() / config() resolution on top) and the debt_gate_check
 * MCP tool, which has no Command context to pull options from.
 */
final class GateOptions
{
    private const VALID_GRADES = ['A', 'B', 'C', 'D', 'F'];

    /**
     * Normalizes a raw --fail-on-grade value: trims, uppercases, validates.
     *
     * @throws InvalidArgumentException when the value is not A, B, C, D, or F
     */
    public static function normalizeGrade(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $grade = strtoupper(trim($raw));

        if (! in_array($grade, self::VALID_GRADES, true)) {
            throw new InvalidArgumentException(
                "Invalid grade value '{$raw}'. Use one of: A, B, C, D, F."
            );
        }

        return $grade;
    }

    /**
     * Normalizes a raw --max-score value to a non-negative integer.
     *
     * @throws InvalidArgumentException when the value is not a non-negative integer
     */
    public static function normalizeScore(int|string|null $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! ctype_digit((string) $raw)) {
            throw new InvalidArgumentException(
                "Invalid score value '{$raw}'. Use a non-negative integer."
            );
        }

        return (int) $raw;
    }
}

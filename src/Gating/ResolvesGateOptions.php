<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Gating;

use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Shared logic for console commands that expose the CI debt gate.
 *
 * Resolves the effective thresholds using precedence: CLI flag → config default
 * → off (null). Validation/normalization itself lives in GateOptions, shared
 * with the debt_gate_check MCP tool.
 *
 * @mixin Command
 */
trait ResolvesGateOptions
{
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

        return GateOptions::normalizeGrade($raw === null ? null : (string) $raw);
    }

    /**
     * Resolve the effective --max-score value from the CLI flag or config default.
     */
    private function resolveMaxScore(): ?int
    {
        $raw = $this->option('max-score') ?? config('debt-tracker.ci.max_score');

        return GateOptions::normalizeScore($raw);
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Gating;

/**
 * Outcome of a CI debt-gate evaluation.
 */
final class GateResult
{
    /**
     * @param  bool  $active  Whether any threshold was configured (gate is enforced)
     * @param  bool  $passed  True when the gate is inactive or no threshold was breached
     * @param  string[]  $reasons  Human-readable breach descriptions (empty when passed)
     * @param  bool  $gradeBreached  True when the grade-floor threshold was breached
     * @param  bool  $scoreBreached  True when the max-score threshold was breached
     */
    public function __construct(
        public readonly bool $active,
        public readonly bool $passed,
        public readonly array $reasons,
        public readonly bool $gradeBreached = false,
        public readonly bool $scoreBreached = false,
    ) {}
}

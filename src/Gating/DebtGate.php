<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Gating;

use TechRaysLabs\DebtTracker\Scoring\GradeResolver;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Evaluates a scan result against absolute CI thresholds (grade floor and/or
 * max score) and reports whether the debt gate passes.
 */
class DebtGate
{
    public function __construct(private readonly GradeResolver $grades) {}

    /**
     * @param  string|null  $failOnGrade  Fail when the grade is this letter or worse (A–F); null disables
     * @param  int|null  $maxScore  Fail when the total score exceeds this value; null disables
     */
    public function evaluate(ScanResult $result, ?string $failOnGrade, ?int $maxScore): GateResult
    {
        $active = $failOnGrade !== null || $maxScore !== null;
        $reasons = [];

        if ($failOnGrade !== null
            && $this->grades->rank($result->grade) >= $this->grades->rank($failOnGrade)) {
            $reasons[] = "grade {$result->grade} is at or below the failure threshold {$failOnGrade}";
        }

        if ($maxScore !== null && $result->totalScore > $maxScore) {
            $reasons[] = "score {$result->totalScore} exceeds the maximum of {$maxScore}";
        }

        return new GateResult(
            active: $active,
            passed: $reasons === [],
            reasons: $reasons,
        );
    }
}

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
     * @param  ScanResult  $result  The scan result to evaluate against the gate thresholds
     * @param  string|null  $failOnGrade  Fail when the grade is this letter or worse (A–F); null disables
     * @param  int|null  $maxScore  Fail when the total score exceeds this value; null disables
     * @return GateResult The outcome of evaluating the gate
     */
    public function evaluate(ScanResult $result, ?string $failOnGrade, ?int $maxScore): GateResult
    {
        $active = $failOnGrade !== null || $maxScore !== null;
        $reasons = [];

        $gradeBreached = $failOnGrade !== null
            && $this->grades->rank($result->grade) >= $this->grades->rank($failOnGrade);

        if ($gradeBreached) {
            $reasons[] = "grade {$result->grade} is at or below the failure threshold {$failOnGrade}";
        }

        $scoreBreached = $maxScore !== null && $result->totalScore > $maxScore;

        if ($scoreBreached) {
            $reasons[] = "score {$result->totalScore} exceeds the maximum of {$maxScore}";
        }

        return new GateResult(
            active: $active,
            passed: $reasons === [],
            reasons: $reasons,
            gradeBreached: $gradeBreached,
            scoreBreached: $scoreBreached,
        );
    }
}

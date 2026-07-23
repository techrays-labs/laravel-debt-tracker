<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Gating\DebtGate;
use TechRaysLabs\DebtTracker\Scoring\GradeResolver;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

function makeGate(): DebtGate
{
    return new DebtGate(new GradeResolver);
}

function makeGateScanResult(string $grade, int $score): ScanResult
{
    return new ScanResult(
        fileResults: [],
        classResults: [],
        totalScore: $score,
        grade: $grade,
        estimatedHours: 0.0,
        byCategory: [],
        generatedAt: new DateTimeImmutable('2026-07-02'),
        projectPath: '/tmp/project',
    );
}

it('fails when the grade is at or below the threshold', function () {
    $result = makeGate()->evaluate(makeGateScanResult('C', 400), 'C', null);

    expect($result->active)->toBeTrue()
        ->and($result->passed)->toBeFalse()
        ->and($result->reasons)->not->toBeEmpty();
});

it('passes when the grade is better than the threshold', function () {
    $result = makeGate()->evaluate(makeGateScanResult('A', 50), 'C', null);

    expect($result->active)->toBeTrue()
        ->and($result->passed)->toBeTrue();
});

it('fails when the score exceeds the max', function () {
    expect(makeGate()->evaluate(makeGateScanResult('A', 501), null, 500)->passed)->toBeFalse();
});

it('passes when the score equals the max', function () {
    expect(makeGate()->evaluate(makeGateScanResult('A', 500), null, 500)->passed)->toBeTrue();
});

it('fails when either of two thresholds is breached', function () {
    // grade B passes the C floor, but score 700 breaches max 500
    expect(makeGate()->evaluate(makeGateScanResult('B', 700), 'C', 500)->passed)->toBeFalse();
});

it('passes when both thresholds are set and neither is breached', function () {
    expect(makeGate()->evaluate(makeGateScanResult('B', 200), 'C', 500)->passed)->toBeTrue();
});

it('is inactive when no thresholds are provided', function () {
    $result = makeGate()->evaluate(makeGateScanResult('F', 5000), null, null);

    expect($result->active)->toBeFalse()
        ->and($result->passed)->toBeTrue()
        ->and($result->reasons)->toBeEmpty();
});

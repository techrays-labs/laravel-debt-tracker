<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Gating\GateOptions;

it('normalizes a valid grade to uppercase', function () {
    expect(GateOptions::normalizeGrade('c'))->toBe('C');
});

it('trims whitespace when normalizing a grade', function () {
    expect(GateOptions::normalizeGrade('  A  '))->toBe('A');
});

it('returns null for a null or empty grade', function () {
    expect(GateOptions::normalizeGrade(null))->toBeNull()
        ->and(GateOptions::normalizeGrade(''))->toBeNull();
});

it('throws on an unknown grade letter', function () {
    GateOptions::normalizeGrade('Z');
})->throws(InvalidArgumentException::class);

it('normalizes a numeric score to an int', function () {
    expect(GateOptions::normalizeScore('500'))->toBe(500)
        ->and(GateOptions::normalizeScore(500))->toBe(500);
});

it('returns null for a null or empty score', function () {
    expect(GateOptions::normalizeScore(null))->toBeNull()
        ->and(GateOptions::normalizeScore(''))->toBeNull();
});

it('throws on a non-numeric score', function () {
    GateOptions::normalizeScore('abc');
})->throws(InvalidArgumentException::class);

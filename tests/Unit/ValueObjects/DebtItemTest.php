<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

function makeDebtItem(int $baseScore = 10, float $ageMultiplier = 1.0, string $ageBand = 'fresh'): DebtItem
{
    return new DebtItem(
        type: 'todo',
        filePath: '/app/Foo.php',
        className: 'Foo',
        methodName: 'bar',
        lineNumber: 42,
        description: 'TODO: fix this',
        baseScore: $baseScore,
        ageMultiplier: $ageMultiplier,
        ageBand: $ageBand,
        ageDays: 10,
        gitAuthor: 'dev',
    );
}

it('calculates finalScore correctly with 1.0 multiplier', function () {
    $item = makeDebtItem(baseScore: 10, ageMultiplier: 1.0);
    expect($item->finalScore())->toBe(10);
});

it('calculates finalScore correctly with 1.5 multiplier', function () {
    $item = makeDebtItem(baseScore: 2, ageMultiplier: 1.5);
    expect($item->finalScore())->toBe(3);
});

it('calculates finalScore correctly with 2.0 multiplier', function () {
    $item = makeDebtItem(baseScore: 5, ageMultiplier: 2.0);
    expect($item->finalScore())->toBe(10);
});

it('calculates finalScore correctly with 3.0 critical multiplier', function () {
    $item = makeDebtItem(baseScore: 8, ageMultiplier: 3.0);
    expect($item->finalScore())->toBe(24);
});

it('rounds finalScore when result is fractional', function () {
    $item = makeDebtItem(baseScore: 3, ageMultiplier: 1.5);
    // 3 * 1.5 = 4.5 → rounds to 5
    expect($item->finalScore())->toBe(5);
});

it('toArray contains all expected keys', function () {
    $item = makeDebtItem(baseScore: 10, ageMultiplier: 2.0, ageBand: 'chronic');
    $array = $item->toArray();

    expect($array)->toHaveKeys([
        'type', 'filePath', 'className', 'methodName', 'lineNumber',
        'description', 'baseScore', 'ageMultiplier', 'ageBand',
        'ageDays', 'gitAuthor', 'finalScore',
    ]);
});

it('toArray finalScore matches finalScore() method', function () {
    $item = makeDebtItem(baseScore: 7, ageMultiplier: 3.0);
    expect($item->toArray()['finalScore'])->toBe($item->finalScore());
});

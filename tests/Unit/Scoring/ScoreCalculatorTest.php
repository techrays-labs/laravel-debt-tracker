<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Scoring\ScoreCalculator;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

function makeItem(string $type, int $baseScore, float $multiplier = 1.0): DebtItem
{
    return new DebtItem(
        type: $type,
        filePath: '/app/Foo.php',
        className: 'Foo',
        methodName: null,
        lineNumber: 1,
        description: 'test item',
        baseScore: $baseScore,
        ageMultiplier: $multiplier,
        ageBand: 'fresh',
        ageDays: 0,
        gitAuthor: null,
    );
}

it('sums finalScore() of all items', function () {
    $calc = new ScoreCalculator;
    $items = [
        makeItem('todo', 2, 1.0),
        makeItem('complexity', 5, 2.0),
        makeItem('coverage', 8, 3.0),
    ];

    // 2 + 10 + 24 = 36
    expect($calc->calculate($items))->toBe(36);
});

it('returns zero for empty items array', function () {
    $calc = new ScoreCalculator;
    expect($calc->calculate([]))->toBe(0);
});

it('groups correctly by category', function () {
    $calc = new ScoreCalculator;
    $items = [
        makeItem('todo', 2, 1.0),
        makeItem('todo', 2, 1.5),
        makeItem('complexity', 5, 1.0),
    ];

    $grouped = $calc->byCategory($items);

    expect($grouped['todo'])->toBe(5);        // 2 + 3
    expect($grouped['complexity'])->toBe(5);
});

it('returns empty array for no items in byCategory', function () {
    $calc = new ScoreCalculator;
    expect($calc->byCategory([]))->toBe([]);
});

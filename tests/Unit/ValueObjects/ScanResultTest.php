<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

// Reuses the global makeDebtItem(baseScore, ...) helper defined in DebtItemTest.php.
function makeScanResultWithItems(array $itemsByFile): ScanResult
{
    $fileResults = [];

    foreach ($itemsByFile as $path => $items) {
        $fileResults[] = new FileDebtResult(
            filePath: $path,
            relativePath: $path,
            items: $items,
            totalScore: array_sum(array_map(static fn (DebtItem $i) => $i->finalScore(), $items)),
            itemCount: count($items),
        );
    }

    return new ScanResult(
        fileResults: $fileResults,
        classResults: [],
        totalScore: 0,
        grade: 'A',
        estimatedHours: 0.0,
        byCategory: [],
        generatedAt: new DateTimeImmutable('2026-01-01'),
        projectPath: '/tmp',
    );
}

it('topItems returns items across all files sorted by final score descending', function () {
    $result = makeScanResultWithItems([
        'a.php' => [makeDebtItem(5), makeDebtItem(20)],
        'b.php' => [makeDebtItem(10)],
    ]);

    $scores = array_map(static fn (DebtItem $i) => $i->finalScore(), $result->topItems());

    expect($scores)->toBe([20, 10, 5]);
});

it('topItems respects the limit', function () {
    $result = makeScanResultWithItems([
        'a.php' => [makeDebtItem(5), makeDebtItem(20), makeDebtItem(10)],
    ]);

    expect($result->topItems(2))->toHaveCount(2);
});

it('topItems returns empty array when there are no items', function () {
    $result = makeScanResultWithItems([]);

    expect($result->topItems())->toBeEmpty();
});

function makeScanResultWithAuthors(array $byAuthor = []): ScanResult
{
    return new ScanResult(
        fileResults: [],
        classResults: [],
        totalScore: 0,
        grade: 'A',
        estimatedHours: 0.0,
        byCategory: [],
        generatedAt: new DateTimeImmutable('2026-01-01'),
        projectPath: '/tmp',
        byAuthor: $byAuthor,
    );
}

it('topAuthors returns entries sorted by score descending', function () {
    $result = makeScanResultWithAuthors([
        'Jane Smith' => 87,
        'John Doe' => 142,
        'Unknown' => 12,
    ]);

    $top = $result->topAuthors();
    $keys = array_keys($top);

    expect($keys[0])->toBe('John Doe');
    expect($keys[1])->toBe('Jane Smith');
    expect($keys[2])->toBe('Unknown');
});

it('topAuthors respects the limit', function () {
    $result = makeScanResultWithAuthors(['A' => 100, 'B' => 80, 'C' => 60]);

    expect($result->topAuthors(2))->toHaveCount(2);
});

it('topAuthors returns empty array when byAuthor is empty', function () {
    $result = makeScanResultWithAuthors([]);

    expect($result->topAuthors())->toBeEmpty();
});

it('byAuthor stores Unknown key for null authors', function () {
    $result = makeScanResultWithAuthors(['Unknown' => 30]);

    expect($result->byAuthor)->toHaveKey('Unknown');
    expect($result->byAuthor['Unknown'])->toBe(30);
});

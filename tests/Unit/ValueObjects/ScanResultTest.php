<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

function makeScanResultWithAuthors(array $byAuthor = []): ScanResult
{
    return new ScanResult(
        fileResults: [],
        classResults: [],
        totalScore: 0,
        grade: 'A',
        estimatedHours: 0.0,
        byCategory: [],
        generatedAt: new \DateTimeImmutable('2026-01-01'),
        projectPath: '/tmp',
        byAuthor: $byAuthor,
    );
}

it('topAuthors returns entries sorted by score descending', function () {
    $result = makeScanResultWithAuthors([
        'Jane Smith' => 87,
        'John Doe'   => 142,
        'Unknown'    => 12,
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

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Agent\AgentFormatSerializer;
use TechRaysLabs\DebtTracker\Agent\AgentSummaryBuilder;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

function makeSerializer(): AgentFormatSerializer
{
    return new AgentFormatSerializer(new AgentSummaryBuilder);
}

function makeSerializerItem(int $baseScore = 10): DebtItem
{
    return new DebtItem(
        type: 'todo',
        filePath: '/app/Foo.php',
        className: 'Foo',
        methodName: 'bar',
        lineNumber: 42,
        description: 'TODO: fix this',
        baseScore: $baseScore,
        ageMultiplier: 1.0,
        ageBand: 'fresh',
        ageDays: 10,
        gitAuthor: 'dev',
    );
}

function makeSerializerScanResult(array $itemsByFile): ScanResult
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

it('includes the schema_version constant', function () {
    $payload = makeSerializer()->serialize(makeSerializerScanResult([]));

    expect($payload['schema_version'])->toBe(AgentFormatSerializer::SCHEMA_VERSION);
});

it('returns empty items and priority arrays for a zero-item scan, not missing keys', function () {
    $payload = makeSerializer()->serialize(makeSerializerScanResult([]));

    expect($payload)->toHaveKeys(['items', 'priority'])
        ->and($payload['items'])->toBe([])
        ->and($payload['priority'])->toBe([]);
});

it('serializes each item with the full agent-format shape', function () {
    $result = makeSerializerScanResult(['a.php' => [makeSerializerItem(baseScore: 10)]]);

    $item = makeSerializer()->serialize($result)['items'][0];

    expect($item)->toHaveKeys([
        'type', 'file', 'line_range', 'class_name', 'method_name',
        'final_score', 'age_band', 'age_days', 'summary',
    ])
        ->and($item['line_range'])->toBe(['start' => 42, 'end' => 42])
        ->and($item['final_score'])->toBe(10)
        ->and($item['summary'])->toBeString()->not->toBeEmpty();
});

it('caps the priority array at the given limit, sorted by final score', function () {
    $result = makeSerializerScanResult([
        'a.php' => [makeSerializerItem(5), makeSerializerItem(20), makeSerializerItem(10)],
    ]);

    $priority = makeSerializer()->serialize($result, limit: 2)['priority'];

    expect($priority)->toHaveCount(2)
        ->and($priority[0]['final_score'])->toBe(20)
        ->and($priority[1]['final_score'])->toBe(10);
});

it('includes file_count and item_count', function () {
    $result = makeSerializerScanResult([
        'a.php' => [makeSerializerItem()],
        'b.php' => [makeSerializerItem(), makeSerializerItem()],
    ]);

    $payload = makeSerializer()->serialize($result);

    expect($payload['file_count'])->toBe(2)
        ->and($payload['item_count'])->toBe(3);
});

it('includes package metadata', function () {
    $payload = makeSerializer()->serialize(makeSerializerScanResult([]));

    expect($payload['meta']['package'])->toBe('techrays-labs/laravel-debt-tracker')
        ->and($payload['meta'])->toHaveKeys(['version', 'generated_at']);
});

it('serializeError returns the {"error": {code, message}} shape', function () {
    $payload = makeSerializer()->serializeError('SCAN_FAILED', 'boom');

    expect($payload)->toBe(['error' => ['code' => 'SCAN_FAILED', 'message' => 'boom']]);
});

it('serializeItemForTool returns the same shape as an "items" entry', function () {
    $item = makeSerializerItem();

    expect(makeSerializer()->serializeItemForTool($item))->toHaveKeys([
        'type', 'file', 'line_range', 'class_name', 'method_name',
        'final_score', 'age_band', 'age_days', 'summary',
    ]);
});

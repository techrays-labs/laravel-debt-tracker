<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Reports\JsonReporter;
use TechRaysLabs\DebtTracker\ValueObjects\ClassDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

function makeJsonScanResult(string $grade = 'B', int $score = 141): ScanResult
{
    $item = new DebtItem(
        type: 'n1_queries',
        filePath: '/app/Services/JobService.php',
        className: 'JobService',
        methodName: 'processAll',
        lineNumber: 42,
        description: "Possible N+1: property fetch '\$job->user' inside loop",
        baseScore: 6,
        ageMultiplier: 1.5,
        ageBand: 'growing',
        ageDays: 45,
        gitAuthor: 'chirag',
    );

    $fileResult = new FileDebtResult(
        filePath: '/app/Services/JobService.php',
        relativePath: 'app/Services/JobService.php',
        items: [$item],
        totalScore: 9,
        itemCount: 1,
    );

    $classResult = new ClassDebtResult(
        className: 'JobService',
        fullyQualifiedName: 'App\\Services\\JobService',
        filePath: '/app/Services/JobService.php',
        items: [$item],
        totalScore: 9,
        itemCount: 1,
    );

    return new ScanResult(
        fileResults: [$fileResult],
        classResults: [$classResult],
        totalScore: $score,
        grade: $grade,
        estimatedHours: $score * 0.25,
        byCategory: ['n1_queries' => $score],
        generatedAt: new DateTimeImmutable('2026-06-08 00:00:00'),
        projectPath: '/app',
    );
}

it('generates valid JSON', function () {
    $reporter = new JsonReporter;
    $json = $reporter->generate(makeJsonScanResult());

    expect(json_decode($json, true))->not->toBeNull();
});

it('schema contains all required top-level keys', function () {
    $reporter = new JsonReporter;
    $data = json_decode($reporter->generate(makeJsonScanResult()), true);

    expect($data)->toHaveKeys([
        'generated_at', 'grade', 'total_score', 'estimated_hours',
        'file_count', 'item_count', 'by_category', 'top_files',
        'top_classes', 'items', 'meta',
    ]);
});

it('items array contains one entry per debt item', function () {
    $reporter = new JsonReporter;
    $data = json_decode($reporter->generate(makeJsonScanResult()), true);

    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0])->toHaveKeys([
        'type', 'file', 'line', 'description',
        'base_score', 'age_days', 'age_band', 'age_multiplier',
        'final_score', 'author',
    ]);
});

it('meta block includes package name and URL', function () {
    $reporter = new JsonReporter;
    $data = json_decode($reporter->generate(makeJsonScanResult()), true);

    expect($data['meta']['package'])->toBe('techrays-labs/laravel-debt-tracker');
    expect($data['meta']['url'])->toContain('github.com/techrays-labs/laravel-debt-tracker');
});

it('writes file to the configured path', function () {
    $reporter = new JsonReporter;
    $path = sys_get_temp_dir().'/debt-test-'.uniqid().'.json';

    $reporter->writeToFile(makeJsonScanResult(), $path);

    expect(file_exists($path))->toBeTrue();
    $data = json_decode(file_get_contents($path), true);
    expect($data['grade'])->toBe('B');

    unlink($path);
});

it('throws ScanFailedException when path is not writable', function () {
    $reporter = new JsonReporter;

    expect(fn () => $reporter->writeToFile(makeJsonScanResult(), '/nonexistent/dir/report.json'))
        ->toThrow(\TechRaysLabs\DebtTracker\Exceptions\ScanFailedException::class);
});

it('grade matches the scan result grade', function () {
    $reporter = new JsonReporter;
    $data = json_decode($reporter->generate(makeJsonScanResult('A', 50)), true);

    expect($data['grade'])->toBe('A');
    expect($data['total_score'])->toBe(50);
});

it('includes authors key in JSON output', function () {
    $result = new \TechRaysLabs\DebtTracker\ValueObjects\ScanResult(
        fileResults: [],
        classResults: [],
        totalScore: 0,
        grade: 'A',
        estimatedHours: 0,
        byCategory: [],
        generatedAt: new \DateTimeImmutable,
        projectPath: '/tmp',
        byAuthor: ['John Doe' => 142],
    );

    $reporter = new \TechRaysLabs\DebtTracker\Reports\JsonReporter;
    $decoded = json_decode($reporter->generate($result), true);

    expect($decoded)->toHaveKey('authors');
    expect($decoded['authors'])->toBeArray();
});

it('each authors entry has author and debt_score keys', function () {
    $result = new \TechRaysLabs\DebtTracker\ValueObjects\ScanResult(
        fileResults: [],
        classResults: [],
        totalScore: 0,
        grade: 'A',
        estimatedHours: 0,
        byCategory: [],
        generatedAt: new \DateTimeImmutable,
        projectPath: '/tmp',
        byAuthor: ['John Doe' => 142, 'Jane Smith' => 87],
    );

    $reporter = new \TechRaysLabs\DebtTracker\Reports\JsonReporter;
    $decoded = json_decode($reporter->generate($result), true);

    expect($decoded['authors'][0])->toHaveKeys(['author', 'debt_score']);
    expect($decoded['authors'][0]['author'])->toBe('John Doe');
    expect($decoded['authors'][0]['debt_score'])->toBe(142);
});

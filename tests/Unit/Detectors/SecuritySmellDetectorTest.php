<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Detectors\SecuritySmellDetector;

$fixture = __DIR__ . '/../../Fixtures/SecuritySmellFixture.php';

it('detects eval() as dangerous function call', function () use ($fixture) {
    // excludePaths: [] so the tests/ directory is NOT excluded
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'eval'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(20);
});

it('detects hardcoded credential', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'Hardcoded credential'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(15);
});

it('detects weak hashing on credential variable', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'Weak hashing'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(10);
});

it('detects SQL concatenation', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'SQL concatenation'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(15);
});

it('detects unsafe unserialize', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'Unsafe unserialize'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(20);
});

it('detects debug leakage (dd)', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    $found = array_filter($items, fn ($i) => str_contains($i->description, 'Debug leakage'));
    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(5);
});

it('detects all 6 smell types in the fixture', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($fixture);

    expect(count($items))->toBe(6);
});

it('returns empty array for a clean file', function () {
    $cleanFixture = __DIR__ . '/../../Fixtures/CleanFile.php';
    $detector = new SecuritySmellDetector(excludePaths: []);
    $items = $detector->detect($cleanFixture);

    expect($items)->toBeEmpty();
});

it('skips files matching security_exclude_paths', function () use ($fixture) {
    // Default excludePaths includes 'tests' — fixture is in tests/ so it is skipped
    $detector = new SecuritySmellDetector(excludePaths: ['tests']);
    $items = $detector->detect($fixture);

    expect($items)->toBeEmpty();
});

it('returns empty when detector is disabled', function () use ($fixture) {
    $detector = new SecuritySmellDetector(enabled: false, excludePaths: []);
    $items = $detector->detect($fixture);

    expect($items)->toBeEmpty();
});

it('works without ast context (regex-only detector)', function () use ($fixture) {
    $detector = new SecuritySmellDetector(excludePaths: []);
    // No 'ast' key passed — should still work
    $items = $detector->detect($fixture, []);

    expect(count($items))->toBe(6);
});

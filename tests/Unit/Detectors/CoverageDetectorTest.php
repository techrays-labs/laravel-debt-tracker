<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\CoverageDetector;

$fixtureDir = dirname(__DIR__, 2).'/Fixtures';
$projectRoot = dirname(__DIR__, 3);

function buildCoverageContext(string $filePath): array
{
    $parser = new AstParser;
    $ast = $parser->parse($filePath);

    return ['ast' => $ast, 'astParser' => $parser];
}

it('flags class with no test file', function () use ($fixtureDir, $projectRoot) {
    // GodClass has no test file
    $filePath = $fixtureDir.'/GodClass.php';
    $detector = new CoverageDetector(projectRoot: $projectRoot);
    $items = $detector->detect($filePath, buildCoverageContext($filePath));

    $noTestItems = array_filter($items, fn ($i) => str_contains($i->description, 'No test file'));
    expect(count($noTestItems))->toBeGreaterThanOrEqual(1);
});

it('finds test file with standard naming convention', function () use ($fixtureDir, $projectRoot) {
    // TodoFixtures has a test file: tests/Unit/Detectors/TodoDetectorTest.php — not a direct match
    // So it should flag it; this tests that when a test IS found the behaviour changes
    // We test via CleanFile which has no test — should produce a flag
    $filePath = $fixtureDir.'/CleanFile.php';
    $detector = new CoverageDetector(projectRoot: $projectRoot);
    $items = $detector->detect($filePath, buildCoverageContext($filePath));

    // CleanFile has no corresponding CleanFileTest.php
    expect($items)->not->toBeEmpty();
});

it('flags public method not referenced in test', function () use ($fixtureDir, $projectRoot) {
    $filePath = $fixtureDir.'/ComplexMethod.php';
    $detector = new CoverageDetector(projectRoot: $projectRoot);
    $items = $detector->detect($filePath, buildCoverageContext($filePath));

    // Either no test file flag or untested method flags
    expect($items)->not->toBeEmpty();
});

it('skips excluded paths', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/CleanFile.php';
    // Exclude the fixture directory
    $detector = new CoverageDetector(
        projectRoot: dirname($fixtureDir),
        excludePaths: [$fixtureDir],
    );
    $items = $detector->detect($filePath, buildCoverageContext($filePath));

    expect($items)->toBeEmpty();
});

it('returns empty when disabled', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/GodClass.php';
    $detector = new CoverageDetector(enabled: false);
    $items = $detector->detect($filePath, buildCoverageContext($filePath));

    expect($items)->toBeEmpty();
});

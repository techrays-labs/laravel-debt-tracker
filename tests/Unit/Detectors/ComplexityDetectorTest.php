<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\ComplexityDetector;

$fixtureDir = dirname(__DIR__, 2).'/Fixtures';

function buildContext(string $filePath): array
{
    $parser = new AstParser;
    $ast = $parser->parse($filePath);

    return ['ast' => $ast, 'astParser' => $parser];
}

it('flags method with complexity above threshold', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/ComplexMethod.php';
    $detector = new ComplexityDetector(complexityThreshold: 10);
    $items = $detector->detect($filePath, buildContext($filePath));

    $complexItems = array_filter($items, fn ($i) => str_contains($i->description, 'cyclomatic'));
    expect(count($complexItems))->toBeGreaterThanOrEqual(1);
});

it('flags long method above line threshold', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/ComplexMethod.php';
    $detector = new ComplexityDetector(methodLengthThreshold: 30);
    $items = $detector->detect($filePath, buildContext($filePath));

    $longItems = array_filter($items, fn ($i) => str_contains($i->description, 'Long method'));
    expect(count($longItems))->toBeGreaterThanOrEqual(1);
});

it('flags god class by method count', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/GodClass.php';
    $detector = new ComplexityDetector(maxPublicMethods: 20);
    $items = $detector->detect($filePath, buildContext($filePath));

    $godItems = array_filter($items, fn ($i) => str_contains($i->description, 'God class'));
    expect(count($godItems))->toBeGreaterThanOrEqual(1);
});

it('flags god class by line count', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/GodClass.php';
    $detector = new ComplexityDetector(classLengthThreshold: 500, maxPublicMethods: 1000);
    $items = $detector->detect($filePath, buildContext($filePath));

    $godItems = array_filter($items, fn ($i) => str_contains($i->description, 'God class'));
    expect(count($godItems))->toBeGreaterThanOrEqual(1);
});

it('flags deep nesting beyond threshold', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/ComplexMethod.php';
    $detector = new ComplexityDetector(nestingDepthThreshold: 4);
    $items = $detector->detect($filePath, buildContext($filePath));

    $nestingItems = array_filter($items, fn ($i) => str_contains($i->description, 'nesting'));
    expect(count($nestingItems))->toBeGreaterThanOrEqual(1);
});

it('does not flag clean methods', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/CleanFile.php';
    $detector = new ComplexityDetector;
    $items = $detector->detect($filePath, buildContext($filePath));

    expect($items)->toBeEmpty();
});

it('returns empty array when disabled', function () use ($fixtureDir) {
    $filePath = $fixtureDir.'/ComplexMethod.php';
    $detector = new ComplexityDetector(enabled: false);
    $items = $detector->detect($filePath, buildContext($filePath));

    expect($items)->toBeEmpty();
});

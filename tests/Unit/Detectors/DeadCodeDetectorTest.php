<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\DeadCodeDetector;

function deadCodeFixtureItems(): array
{
    $fixture = __DIR__ . '/../../Fixtures/DeadCodeFixture.php';
    $parser = new AstParser;
    $ast = $parser->parse($fixture);

    $detector = new DeadCodeDetector;

    return $detector->detect($fixture, ['ast' => $ast]);
}

it('flags unused private method', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'unusedPrivateMethod'));

    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(8);
    expect(array_values($found)[0]->type)->toBe('dead_code');
});

it('flags unused private property', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'unusedProperty'));

    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(5);
});

it('flags unused private constant', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'UNUSED_CONST'));

    expect($found)->not->toBeEmpty();
    expect(array_values($found)[0]->baseScore)->toBe(3);
});

it('does not flag used private method', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'usedPrivateMethod'));

    expect($found)->toBeEmpty();
});

it('does not flag used private property', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'usedProperty')
        && ! str_contains($i->description, 'unused'));

    expect($found)->toBeEmpty();
});

it('does not flag magic __construct method', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, '__construct'));

    expect($found)->toBeEmpty();
});

it('does not flag Laravel lifecycle method boot()', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'boot'));

    expect($found)->toBeEmpty();
});

it('does not flag constructor-promoted properties', function () {
    $items = deadCodeFixtureItems();
    $found = array_filter($items, fn ($i) => str_contains($i->description, 'promotedProperty'));

    expect($found)->toBeEmpty();
});

it('flags exactly 3 items in the fixture (method + property + constant)', function () {
    $items = deadCodeFixtureItems();

    expect(count($items))->toBe(3);
});

it('returns empty array when detector is disabled', function () {
    $fixture = __DIR__ . '/../../Fixtures/DeadCodeFixture.php';
    $parser = new AstParser;
    $ast = $parser->parse($fixture);

    $detector = new DeadCodeDetector(enabled: false);
    $items = $detector->detect($fixture, ['ast' => $ast]);

    expect($items)->toBeEmpty();
});

it('returns empty array when no ast context provided', function () {
    $fixture = __DIR__ . '/../../Fixtures/DeadCodeFixture.php';
    $detector = new DeadCodeDetector;
    $items = $detector->detect($fixture, []);

    expect($items)->toBeEmpty();
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\N1QueryDetector;

$fixtureDir = dirname(__DIR__, 2).'/Fixtures';

it('flags property fetch inside foreach', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    $propFetches = array_filter($items, fn ($i) => str_contains($i->description, 'property fetch'));
    expect($propFetches)->toHaveCount(1);
});

it('flags chained query call inside ->each() closure', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    $queryChains = array_filter($items, fn ($i) => str_contains($i->description, '()->count()'));
    expect($queryChains)->toHaveCount(1);
});

it('does not flag when ->with() precedes the foreach', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    // renderPosts() is suppressed; only 2 items total (processJobs + sendInvoices)
    expect($items)->toHaveCount(2);
});

it('does not flag ignored properties (id, created_at)', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    $idAccess = array_filter($items, fn ($i) => str_contains($i->description, '->id'));
    expect($idAccess)->toBeEmpty();

    $createdAtAccess = array_filter($items, fn ($i) => str_contains($i->description, '->created_at'));
    expect($createdAtAccess)->toBeEmpty();
});

it('does not flag $this property access', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/CleanFile.php');

    $items = $detector->detect($fixtureDir.'/CleanFile.php', ['ast' => $ast]);

    expect($items)->toBeEmpty();
});

it('assigns base score 6 for property fetch', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    $propFetch = array_values(array_filter($items, fn ($i) => str_contains($i->description, 'property fetch')))[0];
    expect($propFetch->baseScore)->toBe(6);
});

it('assigns base score 10 for chained query call', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    $queryChain = array_values(array_filter($items, fn ($i) => str_contains($i->description, '()->count()')))[0];
    expect($queryChain->baseScore)->toBe(10);
});

it('returns empty array for clean file', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/CleanFile.php');

    $items = $detector->detect($fixtureDir.'/CleanFile.php', ['ast' => $ast]);

    expect($items)->toBeEmpty();
});

it('returns empty array when detector is disabled', function () use ($fixtureDir) {
    $detector = new N1QueryDetector(enabled: false);
    $astParser = new AstParser;
    $ast = $astParser->parse($fixtureDir.'/N1QueryFixture.php');

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', ['ast' => $ast]);

    expect($items)->toBeEmpty();
});

it('returns empty array when no AST in context', function () use ($fixtureDir) {
    $detector = new N1QueryDetector;

    $items = $detector->detect($fixtureDir.'/N1QueryFixture.php', []);

    expect($items)->toBeEmpty();
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Support\DebtResultLookup;

function fixturesRoot(): string
{
    // No composer.json here on purpose — avoids DebtTracker's separate
    // DependencyDetector-on-composer.json pass making real network calls.
    return dirname(__DIR__, 3).'/tests/Fixtures';
}

function makeLookup(): DebtResultLookup
{
    // Scoped to the small "Lookup" fixture subdirectory (one seeded
    // complexity item, coverage detection off) — keeps this test's git-blame
    // subprocess calls minimal and item counts deterministic, since it's
    // testing DebtResultLookup's plumbing, not detector behavior.
    return new DebtResultLookup(new DebtTracker([
        'project_root' => fixturesRoot(),
        'scan_paths' => ['Lookup'],
        'detectors' => ['coverage' => false],
    ]));
}

it('findFile locates a known fixture file by relative path', function () {
    $result = makeLookup()->findFile('Lookup/LookupFixture.php', fixturesRoot());

    expect($result)->not->toBeNull()
        ->and($result->relativePath)->toBe('Lookup/LookupFixture.php')
        ->and($result->itemCount)->toBe(1);
});

it('findFile returns null when the file has no debt', function () {
    $result = makeLookup()->findFile('Lookup/CleanLookupFixture.php', fixturesRoot());

    expect($result)->toBeNull();
});

it('findFile throws when the file does not exist', function () {
    makeLookup()->findFile('Lookup/DoesNotExist.php', fixturesRoot());
})->throws(InvalidArgumentException::class);

it('findClass locates a known fixture class by name', function () {
    // ClassAnalyzer currently sets fullyQualifiedName == className (no
    // namespace prefix is captured by the detectors); the bare name is what
    // ShowClassCommand's existing lookup already matches against.
    $result = makeLookup()->findClass('LookupFixture');

    expect($result)->not->toBeNull()
        ->and($result->className)->toBe('LookupFixture');
});

it('findClass returns null for an unknown class', function () {
    $result = makeLookup()->findClass('App\Does\Not\Exist');

    expect($result)->toBeNull();
});

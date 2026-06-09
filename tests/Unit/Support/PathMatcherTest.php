<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Support\PathMatcher;

// ── Plain names ──────────────────────────────────────────────────────────────

it('matches a plain directory name anywhere in the path', function (): void {
    expect(PathMatcher::matches('/var/www/app/Modules/User/tests/UserTest.php', 'tests'))->toBeTrue();
});

it('does not match a plain name that is not in the path', function (): void {
    expect(PathMatcher::matches('/var/www/app/Modules/User/Http/Controllers/UserController.php', 'tests'))->toBeFalse();
});

// ── Literal sub-paths ─────────────────────────────────────────────────────────

it('matches a literal sub-path', function (): void {
    expect(PathMatcher::matches(
        '/var/www/app/Modules/User/tests/UserTest.php',
        'Modules/User/tests'
    ))->toBeTrue();
});

it('does not match a wrong literal sub-path', function (): void {
    expect(PathMatcher::matches(
        '/var/www/app/Modules/Blog/tests/BlogTest.php',
        'Modules/User/tests'
    ))->toBeFalse();
});

// ── Glob patterns ─────────────────────────────────────────────────────────────

it('matches a glob pattern with wildcard for any module', function (): void {
    expect(PathMatcher::matches(
        '/var/www/app/Modules/User/tests/UserTest.php',
        'Modules/*/tests'
    ))->toBeTrue();

    expect(PathMatcher::matches(
        '/var/www/app/Modules/Blog/tests/BlogTest.php',
        'Modules/*/tests'
    ))->toBeTrue();
});

it('glob wildcard does not cross multiple directory segments', function (): void {
    // "Modules/*/tests" should NOT match "Modules/User/Nested/tests" because
    // * is a single-segment wildcard.
    expect(PathMatcher::matches(
        '/var/www/app/Modules/User/Nested/tests/SomeTest.php',
        'Modules/*/tests'
    ))->toBeFalse();
});

it('does not match a glob pattern when the path does not fit', function (): void {
    expect(PathMatcher::matches(
        '/var/www/app/Http/Controllers/UserController.php',
        'Modules/*/tests'
    ))->toBeFalse();
});

// ── Windows-style separators ──────────────────────────────────────────────────

it('normalises backslashes before matching', function (): void {
    expect(PathMatcher::matches(
        'C:\\Projects\\app\\Modules\\User\\tests\\UserTest.php',
        'Modules/*/tests'
    ))->toBeTrue();
});

// ── database/seeders default exclusion (security detector) ───────────────────

it('matches database/seeders sub-path correctly', function (): void {
    expect(PathMatcher::matches(
        '/var/www/database/seeders/UserSeeder.php',
        'database/seeders'
    ))->toBeTrue();
});

// ── nWidart/laravel-modules real-world scenario ───────────────────────────────
// When scan_paths = ['Modules'], Finder roots inside Modules/ so relative paths
// are "User/tests/..." — no "Modules/" prefix.  The fix matches the ABSOLUTE
// path so "Modules/User/tests" is always found regardless of scan root.

it('matches absolute path from Modules root — simulates nWidart modules layout', function (): void {
    // Absolute path as Finder::getRealPath() returns it
    $absolute = '/var/www/project/Modules/User/tests/Feature/Http/Controllers/UserControllerTest.php';

    expect(PathMatcher::matches($absolute, 'Modules/User/tests'))->toBeTrue();
    expect(PathMatcher::matches($absolute, 'Modules/*/tests'))->toBeTrue();
    expect(PathMatcher::matches($absolute, 'Modules/User/tests/Feature/Http/Controllers'))->toBeTrue();
});

it('does not exclude non-test files under Modules/User', function (): void {
    $absolute = '/var/www/project/Modules/User/Http/Controllers/UserController.php';

    expect(PathMatcher::matches($absolute, 'Modules/User/tests'))->toBeFalse();
    expect(PathMatcher::matches($absolute, 'Modules/*/tests'))->toBeFalse();
});

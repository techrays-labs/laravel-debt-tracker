<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Git\GitBlameReader;

// --- resolveAgeBand boundary tests ---

it('resolves age band as fresh for 0 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(0))->toBe('fresh');
});

it('resolves age band as fresh for 29 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(29))->toBe('fresh');
});

it('resolves age band as growing for 30 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(30))->toBe('growing');
});

it('resolves age band as growing for 89 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(89))->toBe('growing');
});

it('resolves age band as chronic for 90 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(90))->toBe('chronic');
});

it('resolves age band as chronic for 179 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(179))->toBe('chronic');
});

it('resolves age band as critical for 180 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(180))->toBe('critical');
});

it('resolves age band as critical for 500 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(500))->toBe('critical');
});

// --- resolveAgeMultiplier tests ---

it('returns 1.0 multiplier for fresh band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('fresh'))->toBe(1.0);
});

it('returns 1.5 multiplier for growing band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('growing'))->toBe(1.5);
});

it('returns 2.0 multiplier for chronic band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('chronic'))->toBe(2.0);
});

it('returns 3.0 multiplier for critical band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('critical'))->toBe(3.0);
});

it('returns 1.0 multiplier for unknown band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('unknown'))->toBe(1.0);
});

// --- Fallback when git is unavailable ---

it('getLineAge returns null gracefully for non-existent file when git unavailable', function () {
    $reader = new class('/tmp/non-existent-project') extends GitBlameReader
    {
        public function isGitAvailable(): bool
        {
            return false;
        }
    };

    expect($reader->getLineAge('/tmp/non-existent-project/SomeFile.php', 1))->toBeNull();
});

it('getLineAuthor returns null when git unavailable', function () {
    $reader = new class('/tmp/non-existent-project') extends GitBlameReader
    {
        public function isGitAvailable(): bool
        {
            return false;
        }
    };

    expect($reader->getLineAuthor('/tmp/non-existent-project/SomeFile.php', 1))->toBeNull();
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Scoring\GradeResolver;

it('returns A for score 0', function () {
    expect((new GradeResolver)->resolve(0))->toBe('A');
});

it('returns A for score 100', function () {
    expect((new GradeResolver)->resolve(100))->toBe('A');
});

it('returns B for score 101', function () {
    expect((new GradeResolver)->resolve(101))->toBe('B');
});

it('returns B for score 300', function () {
    expect((new GradeResolver)->resolve(300))->toBe('B');
});

it('returns C for score 301', function () {
    expect((new GradeResolver)->resolve(301))->toBe('C');
});

it('returns C for score 600', function () {
    expect((new GradeResolver)->resolve(600))->toBe('C');
});

it('returns D for score 601', function () {
    expect((new GradeResolver)->resolve(601))->toBe('D');
});

it('returns D for score 1000', function () {
    expect((new GradeResolver)->resolve(1000))->toBe('D');
});

it('returns F for score 1001', function () {
    expect((new GradeResolver)->resolve(1001))->toBe('F');
});

it('returns F for score above 1000', function () {
    expect((new GradeResolver)->resolve(9999))->toBe('F');
});

it('returns correct color for grade A', function () {
    expect((new GradeResolver)->color('A'))->toBe('green');
});

it('returns correct color for grade B', function () {
    expect((new GradeResolver)->color('B'))->toBe('cyan');
});

it('returns correct color for grade C', function () {
    expect((new GradeResolver)->color('C'))->toBe('yellow');
});

it('returns correct color for grade D', function () {
    expect((new GradeResolver)->color('D'))->toBe('red');
});

it('returns correct color for grade F', function () {
    expect((new GradeResolver)->color('F'))->toBe('red');
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Scoring\HoursEstimator;

it('calculates hours correctly at default rate', function () {
    $estimator = new HoursEstimator;
    expect($estimator->estimate(400))->toBe(100.0);
});

it('calculates hours with custom rate', function () {
    $estimator = new HoursEstimator;
    expect($estimator->estimate(100, 0.5))->toBe(50.0);
});

it('returns null cost when rate not configured', function () {
    $estimator = new HoursEstimator;
    expect($estimator->estimateCost(100.0, null))->toBeNull();
});

it('calculates cost when hourly rate provided', function () {
    $estimator = new HoursEstimator;
    expect($estimator->estimateCost(10.0, 150))->toBe(1500.0);
});

it('returns zero hours for zero score', function () {
    $estimator = new HoursEstimator;
    expect($estimator->estimate(0))->toBe(0.0);
});

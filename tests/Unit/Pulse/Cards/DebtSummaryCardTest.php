<?php

declare(strict_types=1);

use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Pulse;
use Livewire\Attributes\Lazy;
use TechRaysLabs\DebtTracker\Pulse\Cards\DebtSummaryCard;

beforeAll(function (): void {
    if (! class_exists(Pulse::class)) {
        test()->skip('laravel/pulse not installed');
    }
});

it('extends the Pulse Card base class', function (): void {
    expect(DebtSummaryCard::class)->toExtend(Card::class);
});

it('has the Lazy attribute', function (): void {
    $reflection = new ReflectionClass(DebtSummaryCard::class);
    $attributes = $reflection->getAttributes(Lazy::class);
    expect($attributes)->not->toBeEmpty();
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Pulse\Cards\DebtSummaryCard;

beforeAll(function (): void {
    if (! class_exists(\Laravel\Pulse\Pulse::class)) {
        test()->skip('laravel/pulse not installed');
    }
});

it('extends the Pulse Card base class', function (): void {
    expect(DebtSummaryCard::class)->toExtend(\Laravel\Pulse\Livewire\Card::class);
});

it('has the Lazy attribute', function (): void {
    $reflection = new ReflectionClass(DebtSummaryCard::class);
    $attributes = $reflection->getAttributes(\Livewire\Attributes\Lazy::class);
    expect($attributes)->not->toBeEmpty();
});

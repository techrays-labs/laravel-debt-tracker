<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Pulse\Cards\DebtFilesCard;

beforeAll(function (): void {
    if (! class_exists(\Laravel\Pulse\Pulse::class)) {
        test()->skip('laravel/pulse not installed');
    }
});

it('extends the Pulse Card base class', function (): void {
    expect(DebtFilesCard::class)->toExtend(\Laravel\Pulse\Livewire\Card::class);
});

it('has the Lazy attribute', function (): void {
    $reflection = new ReflectionClass(DebtFilesCard::class);
    $attributes = $reflection->getAttributes(\Livewire\Attributes\Lazy::class);
    expect($attributes)->not->toBeEmpty();
});

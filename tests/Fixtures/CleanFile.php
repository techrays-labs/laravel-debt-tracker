<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures;

/**
 * Fixture: completely clean file — no debt items should be detected here.
 */
class CleanFile
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }

    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function isEven(int $n): bool
    {
        return $n % 2 === 0;
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures\Lookup;

/**
 * Fixture: no debt at all — used to assert DebtResultLookup returns null.
 */
class CleanLookupFixture
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}

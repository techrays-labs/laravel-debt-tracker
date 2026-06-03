<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures;

/**
 * Fixture class containing exactly:
 * - 3 TODO comments (line comments)
 * - 2 FIXME comments (block comments)
 * - 1 @todo (docblock)
 * Total: 6 items × base score 2 = 12 raw score (before age multiplier)
 */
class TodoFixtures
{
    // TODO: replace this with a proper implementation
    public function methodOne(): string
    {
        // TODO: handle edge case for empty strings
        return 'hello';
    }

    /* FIXME: this causes a null pointer dereference on line 25 */
    public function methodTwo(): int
    {
        /* FIXME: remove magic number */
        return 42;
    }

    /**
     * @todo refactor this method to use the new payment gateway
     */
    public function methodThree(): void
    {
        // clean line — no debt here
    }
}

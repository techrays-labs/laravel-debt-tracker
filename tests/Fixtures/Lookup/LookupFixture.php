<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures\Lookup;

/**
 * Fixture: exactly one deterministic complexity debt item (cyclomatic
 * complexity 11 > default threshold 10), kept in its own subdirectory so
 * DebtResultLookup tests scan a small, isolated set of files. Coverage
 * detection is disabled in the test config so this stays the only item.
 */
class LookupFixture
{
    public function tooComplex(int $n): string
    {
        $result = 'start';

        if ($n === 1) {
            $result .= '1';
        }
        if ($n === 2) {
            $result .= '2';
        }
        if ($n === 3) {
            $result .= '3';
        }
        if ($n === 4) {
            $result .= '4';
        }
        if ($n === 5) {
            $result .= '5';
        }
        if ($n === 6) {
            $result .= '6';
        }
        if ($n === 7) {
            $result .= '7';
        }
        if ($n === 8) {
            $result .= '8';
        }
        if ($n === 9) {
            $result .= '9';
        }
        if ($n === 10) {
            $result .= '10';
        }

        return $result;
    }
}

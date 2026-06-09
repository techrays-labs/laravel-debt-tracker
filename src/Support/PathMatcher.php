<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Support;

/**
 * Utility for matching file paths against exclusion patterns.
 *
 * Supports three pattern styles:
 *   - Plain names:   "tests"                   — matches any path containing the segment
 *   - Literal paths: "Modules/User/tests"   — matches paths containing that sub-path
 *   - Glob paths:    "Modules/{star}/tests" — {star} matches one directory segment only
 *
 * Path separators are normalised to `/` before matching so the same
 * pattern works on both Unix and Windows.
 */
final class PathMatcher
{
    /**
     * Returns true when $filePath matches the given $pattern.
     *
     * @param string $filePath Absolute or relative file path (any separator).
     * @param string $pattern  Plain segment, literal sub-path, or glob pattern.
     */
    public static function matches(string $filePath, string $pattern): bool
    {
        $filePath = str_replace('\\', '/', $filePath);
        $pattern  = str_replace('\\', '/', $pattern);

        if (! str_contains($pattern, '*')) {
            // Fast path: simple substring / segment match.
            return str_contains($filePath, $pattern);
        }

        // Glob pattern: `*` matches any single directory segment (not `/`).
        // preg_quote escapes `*` as `\*`; we then restore it as `[^/]+`.
        $regex = '#'.str_replace('\*', '[^/]+', preg_quote($pattern, '#')).'#i';

        return (bool) preg_match($regex, $filePath);
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects security smells in PHP files via regex pattern matching.
 */
class SecuritySmellDetector implements DetectorInterface
{
    private const BASE_SCORE_DANGEROUS = 20;

    private const BASE_SCORE_CREDENTIAL = 15;

    private const BASE_SCORE_SQL = 15;

    private const BASE_SCORE_UNSERIALIZE = 20;

    private const BASE_SCORE_WEAK_HASH = 10;

    private const BASE_SCORE_DEBUG = 5;

    private const CREDENTIAL_KEYWORDS = ['password', 'secret', 'api_key', 'token', 'passwd', 'private_key'];

    /**
     * @param  string[]  $excludePaths  File paths containing any of these strings are skipped
     */
    public function __construct(
        private readonly bool $enabled = true,
        private readonly array $excludePaths = ['tests', 'database/seeders'],
    ) {}

    public function getName(): string
    {
        return 'security';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return DebtItem[]
     */
    public function detect(string $filePath, array $context = []): array
    {
        if (! $this->enabled) {
            return [];
        }

        $normalised = str_replace('\\', '/', $filePath);

        foreach ($this->excludePaths as $excluded) {
            if (str_contains($normalised, $excluded)) {
                return [];
            }
        }

        $source = @file_get_contents($filePath);

        if ($source === false) {
            return [];
        }

        /** @var GitBlameReader|null $git */
        $git = $context['git'] ?? null;
        $items = [];
        $lines = explode("\n", $source);

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;

            // Skip comment-only lines to reduce false positives
            $trimmed = ltrim($line);

            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
                continue;
            }

            array_push($items, ...$this->scanLine($line, $filePath, $lineNumber, $git));
        }

        return $items;
    }

    /**
     * @return DebtItem[]
     */
    private function scanLine(string $line, string $filePath, int $lineNumber, ?GitBlameReader $git): array
    {
        $items = [];

        // 1. Dangerous function calls
        if (preg_match('/\b(eval|shell_exec|exec|system|passthru)\s*\(/', $line, $m)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                "Dangerous function call: {$m[1]}()",
                self::BASE_SCORE_DANGEROUS,
            );
        }

        // 2. Hardcoded credentials — $variable_name = 'literal'
        $credPattern = implode('|', self::CREDENTIAL_KEYWORDS);

        if (preg_match('/\$\w*(?:'.$credPattern.')\w*\s*=\s*[\'"][^\'"]+[\'"]/i', $line)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                'Hardcoded credential: sensitive variable assigned a string literal',
                self::BASE_SCORE_CREDENTIAL,
            );
        }

        // 3. Weak hashing on a credential-like variable (both patterns on same line)
        if (preg_match('/\b(md5|sha1)\s*\(/', $line)
            && preg_match('/(?:'.$credPattern.')/i', $line)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                'Weak hashing: md5/sha1 used on a credential-like variable',
                self::BASE_SCORE_WEAK_HASH,
            );
        }

        // 4. SQL string concatenation or interpolation
        if (preg_match('/[\'"]?\s*(?:SELECT|INSERT|UPDATE|DELETE)\b.+[\'"]?\s*\./i', $line)
            || preg_match('/[\'"]?\s*(?:SELECT|INSERT|UPDATE|DELETE)\b[^;]*"\$\w+/i', $line)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                'SQL concatenation: query built with string concatenation or interpolation',
                self::BASE_SCORE_SQL,
            );
        }

        // 5. unserialize() with a variable argument
        if (preg_match('/\bunserialize\s*\(\s*\$/', $line)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                'Unsafe unserialize: passing variable input to unserialize()',
                self::BASE_SCORE_UNSERIALIZE,
            );
        }

        // 6. Debug leakage
        if (preg_match('/\b(var_dump|dd|dump)\s*\(/', $line)) {
            $items[] = $this->makeItem($filePath, $lineNumber, $git,
                'Debug leakage: debug statement left in production code',
                self::BASE_SCORE_DEBUG,
            );
        }

        return $items;
    }

    private function makeItem(
        string $filePath,
        int $lineNumber,
        ?GitBlameReader $git,
        string $description,
        int $baseScore,
    ): DebtItem {
        $ageDays = $git ? ($git->getLineAge($filePath, $lineNumber) ?? 0) : 0;
        $ageBand = $git ? $git->resolveAgeBand($ageDays) : 'fresh';
        $multiplier = $git ? $git->resolveAgeMultiplier($ageBand) : 1.0;
        $author = $git ? $git->getLineAuthor($filePath, $lineNumber) : null;

        return new DebtItem(
            type: 'security',
            filePath: $filePath,
            className: null,
            methodName: null,
            lineNumber: $lineNumber,
            description: $description,
            baseScore: $baseScore,
            ageMultiplier: $multiplier,
            ageBand: $ageBand,
            ageDays: $ageDays,
            gitAuthor: $author,
        );
    }
}

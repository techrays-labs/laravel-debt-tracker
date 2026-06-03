<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects TODO / FIXME / HACK / XXX / TEMP / REFACTOR annotations in PHP files.
 */
class TodoDetector implements DetectorInterface
{
    private const KEYWORDS = ['TODO', 'FIXME', 'HACK', 'XXX', 'TEMP', 'REFACTOR'];

    private const BASE_SCORE = 2;

    public function __construct(private readonly bool $enabled = true) {}

    public function getName(): string
    {
        return 'todos';
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

        $source = @file_get_contents($filePath);

        if ($source === false) {
            return [];
        }

        /** @var GitBlameReader|null $git */
        $git = $context['git'] ?? null;
        $items = [];
        $lines = explode("\n", $source);

        $keywordPattern = implode('|', self::KEYWORDS);

        $patterns = [
            '/\/\/\s*('.$keywordPattern.'):?\s*(.+)/i',
            '/\/\*+\s*('.$keywordPattern.'):?\s*(.+?)\*\//is',
            '/@('.$keywordPattern.')\s+(.+)/i',
        ];

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $keyword = strtoupper($matches[1]);
                    $description = trim(substr($matches[2], 0, 200));

                    $ageDays = $git ? ($git->getLineAge($filePath, $lineNumber) ?? 0) : 0;
                    $ageBand = $git ? $git->resolveAgeBand($ageDays) : 'fresh';
                    $multiplier = $git ? $git->resolveAgeMultiplier($ageBand) : 1.0;
                    $author = $git ? $git->getLineAuthor($filePath, $lineNumber) : null;

                    $items[] = new DebtItem(
                        type: 'todo',
                        filePath: $filePath,
                        className: null,
                        methodName: null,
                        lineNumber: $lineNumber,
                        description: "{$keyword}: {$description}",
                        baseScore: self::BASE_SCORE,
                        ageMultiplier: $multiplier,
                        ageBand: $ageBand,
                        ageDays: $ageDays,
                        gitAuthor: $author,
                    );

                    // Only match the first pattern per line to avoid duplicates.
                    break;
                }
            }
        }

        return $items;
    }
}

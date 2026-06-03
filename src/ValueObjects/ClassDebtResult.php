<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\ValueObjects;

/**
 * Aggregated debt results for a single class.
 */
final class ClassDebtResult
{
    /**
     * @param  string  $className  Short class name
     * @param  string  $fullyQualifiedName  Fully-qualified class name
     * @param  string  $filePath  Absolute path to the class file
     * @param  DebtItem[]  $items  All debt items belonging to this class
     * @param  int  $totalScore  Sum of all item final scores
     * @param  int  $itemCount  Number of debt items detected
     */
    public function __construct(
        public readonly string $className,
        public readonly string $fullyQualifiedName,
        public readonly string $filePath,
        public readonly array $items,
        public readonly int $totalScore,
        public readonly int $itemCount,
    ) {}
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\ValueObjects;

/**
 * Top-level result of a full project scan.
 */
final class ScanResult
{
    /**
     * @param  FileDebtResult[]  $fileResults  Per-file aggregated results
     * @param  ClassDebtResult[]  $classResults  Per-class aggregated results
     * @param  int  $totalScore  Project-wide total debt score
     * @param  string  $grade  Overall grade: A, B, C, D, or F
     * @param  float  $estimatedHours  Estimated remediation hours
     * @param  array<string,int>  $byCategory  Score per debt category
     * @param  \DateTimeImmutable  $generatedAt  Timestamp of the scan
     * @param  string  $projectPath  Absolute path to the scanned project
     * @param  array<string,int>  $byAuthor    Total debt score per git author
     */
    public function __construct(
        public readonly array $fileResults,
        public readonly array $classResults,
        public readonly int $totalScore,
        public readonly string $grade,
        public readonly float $estimatedHours,
        public readonly array $byCategory,
        public readonly \DateTimeImmutable $generatedAt,
        public readonly string $projectPath,
        public readonly array $byAuthor = [],
    ) {}

    /**
     * Returns the N files with the highest total debt score.
     *
     * @return FileDebtResult[]
     */
    public function topFiles(int $n = 10): array
    {
        $sorted = $this->fileResults;

        usort($sorted, static fn (FileDebtResult $a, FileDebtResult $b) => $b->totalScore <=> $a->totalScore);

        return array_slice($sorted, 0, $n);
    }

    /**
     * Returns the N classes with the highest total debt score.
     *
     * @return ClassDebtResult[]
     */
    public function topClasses(int $n = 10): array
    {
        $sorted = $this->classResults;

        usort($sorted, static fn (ClassDebtResult $a, ClassDebtResult $b) => $b->totalScore <=> $a->totalScore);

        return array_slice($sorted, 0, $n);
    }

    /**
     * Returns the N authors with the highest total debt score, sorted descending.
     *
     * @return array<string,int>
     */
    public function topAuthors(int $n = 10): array
    {
        $sorted = $this->byAuthor;
        arsort($sorted);

        return array_slice($sorted, 0, $n, true);
    }

    /**
     * Returns the total number of individual debt items across all files.
     */
    public function totalItems(): int
    {
        return array_sum(array_map(
            static fn (FileDebtResult $r) => $r->itemCount,
            $this->fileResults
        ));
    }
}

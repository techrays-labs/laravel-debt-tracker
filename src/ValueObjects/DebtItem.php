<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\ValueObjects;

/**
 * Represents a single unit of technical debt detected in the codebase.
 */
final class DebtItem
{
    /**
     * @param  string  $type  The debt category: 'todo', 'complexity', 'coverage', 'dependency', 'git_age'
     * @param  string  $filePath  Absolute path to the file containing the debt
     * @param  string|null  $className  Class containing the debt item (if applicable)
     * @param  string|null  $methodName  Method containing the debt item (if applicable)
     * @param  int  $lineNumber  Line number where the debt was detected
     * @param  string  $description  Human-readable description of the debt
     * @param  int  $baseScore  Raw score before age multiplier
     * @param  float  $ageMultiplier  Multiplier based on how long the debt has existed
     * @param  string  $ageBand  Age category: 'fresh', 'growing', 'chronic', 'critical'
     * @param  int  $ageDays  Age of the debt item in days
     * @param  string|null  $gitAuthor  Author of the debt item from git blame
     */
    public function __construct(
        public readonly string $type,
        public readonly string $filePath,
        public readonly ?string $className,
        public readonly ?string $methodName,
        public readonly int $lineNumber,
        public readonly string $description,
        public readonly int $baseScore,
        public readonly float $ageMultiplier,
        public readonly string $ageBand,
        public readonly int $ageDays,
        public readonly ?string $gitAuthor,
    ) {}

    /**
     * Returns the final score after applying the age multiplier.
     */
    public function finalScore(): int
    {
        return (int) round($this->baseScore * $this->ageMultiplier);
    }

    /**
     * Serialises the item to an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'filePath' => $this->filePath,
            'className' => $this->className,
            'methodName' => $this->methodName,
            'lineNumber' => $this->lineNumber,
            'description' => $this->description,
            'baseScore' => $this->baseScore,
            'ageMultiplier' => $this->ageMultiplier,
            'ageBand' => $this->ageBand,
            'ageDays' => $this->ageDays,
            'gitAuthor' => $this->gitAuthor,
            'finalScore' => $this->finalScore(),
        ];
    }
}

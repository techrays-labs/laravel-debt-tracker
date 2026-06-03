<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects code complexity issues: long methods, God classes, high cyclomatic
 * complexity, and deep nesting.
 */
class ComplexityDetector implements DetectorInterface
{
    public function __construct(
        private readonly bool $enabled = true,
        private readonly int $methodLengthThreshold = 30,
        private readonly int $classLengthThreshold = 500,
        private readonly int $maxPublicMethods = 20,
        private readonly int $nestingDepthThreshold = 4,
        private readonly int $complexityThreshold = 10,
    ) {}

    public function getName(): string
    {
        return 'complexity';
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

        /** @var array<Stmt>|null $ast */
        $ast = $context['ast'] ?? null;

        if ($ast === null) {
            return [];
        }

        /** @var AstParser $astParser */
        $astParser = $context['astParser'] ?? new AstParser;

        /** @var GitBlameReader|null $git */
        $git = $context['git'] ?? null;

        $items = [];

        foreach ($astParser->getClasses($ast) as $class) {
            $items = array_merge($items, $this->analyseClass($class, $filePath, $astParser, $git));
        }

        return $items;
    }

    /** @return DebtItem[] */
    private function analyseClass(
        ClassLike $class,
        string $filePath,
        AstParser $astParser,
        ?GitBlameReader $git,
    ): array {
        $items = [];
        $className = $class->name !== null ? $class->name->name : 'Anonymous';

        // God class check
        $classLine = $class->getStartLine();
        $classLines = $class->getEndLine() - $class->getStartLine();
        $methods = $astParser->getMethods($class);

        $publicMethodCount = count(array_filter(
            $methods,
            static fn ($m) => $m->isPublic()
        ));

        if ($classLines >= $this->classLengthThreshold || $publicMethodCount > $this->maxPublicMethods) {
            [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $classLine);

            $items[] = new DebtItem(
                type: 'complexity',
                filePath: $filePath,
                className: $className,
                methodName: null,
                lineNumber: $classLine,
                description: "God class: {$classLines} lines, {$publicMethodCount} public methods",
                baseScore: 15,
                ageMultiplier: $multiplier,
                ageBand: $ageBand,
                ageDays: $ageDays,
                gitAuthor: $author,
            );
        }

        // Per-method checks
        foreach ($methods as $method) {
            $methodName = $method->name->name;
            $startLine = $method->getStartLine();

            // Long method
            $stmtCount = $astParser->countStatements($method);

            if ($stmtCount > $this->methodLengthThreshold) {
                [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $startLine);

                $items[] = new DebtItem(
                    type: 'complexity',
                    filePath: $filePath,
                    className: $className,
                    methodName: $methodName,
                    lineNumber: $startLine,
                    description: "Long method: {$stmtCount} statements (threshold: {$this->methodLengthThreshold})",
                    baseScore: 5,
                    ageMultiplier: $multiplier,
                    ageBand: $ageBand,
                    ageDays: $ageDays,
                    gitAuthor: $author,
                );
            }

            // Cyclomatic complexity
            $complexity = $astParser->measureCyclomaticComplexity($method);

            if ($complexity > $this->complexityThreshold) {
                [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $startLine);

                $excess = $complexity - $this->complexityThreshold;

                $items[] = new DebtItem(
                    type: 'complexity',
                    filePath: $filePath,
                    className: $className,
                    methodName: $methodName,
                    lineNumber: $startLine,
                    description: "High cyclomatic complexity: {$complexity} (threshold: {$this->complexityThreshold})",
                    baseScore: $excess * 2,
                    ageMultiplier: $multiplier,
                    ageBand: $ageBand,
                    ageDays: $ageDays,
                    gitAuthor: $author,
                );
            }

            // Deep nesting
            $depth = $astParser->measureMaxNestingDepth($method);

            if ($depth > $this->nestingDepthThreshold) {
                [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $startLine);

                $items[] = new DebtItem(
                    type: 'complexity',
                    filePath: $filePath,
                    className: $className,
                    methodName: $methodName,
                    lineNumber: $startLine,
                    description: "Deep nesting: {$depth} levels (threshold: {$this->nestingDepthThreshold})",
                    baseScore: 4,
                    ageMultiplier: $multiplier,
                    ageBand: $ageBand,
                    ageDays: $ageDays,
                    gitAuthor: $author,
                );
            }
        }

        return $items;
    }

    /** @return array{int, string, float, string|null} */
    private function gitData(?GitBlameReader $git, string $filePath, int $lineNumber): array
    {
        $ageDays = $git ? ($git->getLineAge($filePath, $lineNumber) ?? 0) : 0;
        $ageBand = $git ? $git->resolveAgeBand($ageDays) : 'fresh';
        $multiplier = $git ? $git->resolveAgeMultiplier($ageBand) : 1.0;
        $author = $git ? $git->getLineAuthor($filePath, $lineNumber) : null;

        return [$ageDays, $ageBand, $multiplier, $author];
    }
}

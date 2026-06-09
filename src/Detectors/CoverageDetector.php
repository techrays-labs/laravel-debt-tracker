<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use PhpParser\Node\Stmt;
use TechRaysLabs\DebtTracker\Analyzers\AstParser;
use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\Support\PathMatcher;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects missing test coverage using file-system heuristics (no runtime required).
 */
class CoverageDetector implements DetectorInterface
{
    /** @var string[] */
    private const DEFAULT_EXCLUDED = [
        'app/Http/Middleware',
        'app/Providers',
        'app/Console/Kernel.php',
    ];

    public function __construct(
        private readonly bool $enabled = true,
        private readonly string $projectRoot = '',
        /** @var string[] */
        private readonly array $excludePaths = self::DEFAULT_EXCLUDED,
    ) {}

    public function getName(): string
    {
        return 'coverage';
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

        if ($this->isExcluded($filePath)) {
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

        $classes = $astParser->getClasses($ast);

        if (empty($classes)) {
            return [];
        }

        $items = [];

        foreach ($classes as $class) {
            $className = $class->name?->name;

            if ($className === null) {
                continue;
            }

            $testFile = $this->findTestFile($className);

            $ageDays = $git ? ($git->getFileAge($filePath) ?? 0) : 0;
            $ageBand = $git ? $git->resolveAgeBand($ageDays) : 'fresh';
            $multiplier = $git ? $git->resolveAgeMultiplier($ageBand) : 1.0;
            $author = null;

            if ($testFile === null) {
                $items[] = new DebtItem(
                    type: 'coverage',
                    filePath: $filePath,
                    className: $className,
                    methodName: null,
                    lineNumber: $class->getStartLine(),
                    description: "No test file found for class {$className}",
                    baseScore: 8,
                    ageMultiplier: $multiplier,
                    ageBand: $ageBand,
                    ageDays: $ageDays,
                    gitAuthor: $author,
                );

                continue;
            }

            // Check each public method
            $testContent = @file_get_contents($testFile) ?: '';
            $methods = $astParser->getMethods($class);

            foreach ($methods as $method) {
                if (! $method->isPublic() || $method->name->name === '__construct') {
                    continue;
                }

                $methodName = $method->name->name;

                if (! $this->methodReferencedInTest($methodName, $testContent)) {
                    $items[] = new DebtItem(
                        type: 'coverage',
                        filePath: $filePath,
                        className: $className,
                        methodName: $methodName,
                        lineNumber: $method->getStartLine(),
                        description: "Public method {$className}::{$methodName}() has no test reference",
                        baseScore: 4,
                        ageMultiplier: $multiplier,
                        ageBand: $ageBand,
                        ageDays: $ageDays,
                        gitAuthor: $author,
                    );
                }
            }
        }

        return $items;
    }

    private function isExcluded(string $filePath): bool
    {
        foreach ($this->excludePaths as $excluded) {
            if (PathMatcher::matches($filePath, $excluded)) {
                return true;
            }
        }

        return false;
    }

    private function findTestFile(string $className): ?string
    {
        $root = rtrim($this->projectRoot, DIRECTORY_SEPARATOR);
        $tests = $root.DIRECTORY_SEPARATOR.'tests';

        if (! is_dir($tests)) {
            return null;
        }

        $candidates = [
            $tests.'/Unit/'.$className.'Test.php',
            $tests.'/Feature/'.$className.'Test.php',
            $tests.'/Unit/'.$className.'Spec.php',
            $tests.'/Feature/'.$className.'Spec.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Recursive search
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tests, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $name = $file->getFilename();
            if ($name === $className.'Test.php' || $name === $className.'Spec.php') {
                return $file->getRealPath();
            }
        }

        return null;
    }

    private function methodReferencedInTest(string $methodName, string $testContent): bool
    {
        return str_contains($testContent, "'{$methodName}'")
            || str_contains($testContent, "\"{$methodName}\"")
            || str_contains($testContent, "->{$methodName}(")
            || str_contains($testContent, "::{$methodName}(");
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects unused private methods, properties, and constants within PHP classes.
 * Scope is limited to within-class references only — no cross-file analysis.
 */
class DeadCodeDetector implements DetectorInterface
{
    private const BASE_SCORE_METHOD = 8;

    private const BASE_SCORE_PROPERTY = 5;

    private const BASE_SCORE_CONSTANT = 3;

    private const MAGIC_METHODS = [
        '__construct', '__destruct', '__get', '__set', '__isset', '__unset',
        '__call', '__callStatic', '__toString', '__invoke', '__clone',
        '__debugInfo', '__serialize', '__unserialize',
    ];

    private const LIFECYCLE_METHODS = [
        'boot', 'booted', 'register', 'handle', 'fire', 'subscribe', 'setUp', 'tearDown',
    ];

    /**
     * @param  string[]  $ignoreMethods  Extra method names to never flag
     */
    public function __construct(
        private readonly bool $enabled = true,
        private readonly array $ignoreMethods = [],
    ) {}

    public function getName(): string
    {
        return 'dead_code';
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

        $ast = $context['ast'] ?? null;

        if ($ast === null) {
            return [];
        }

        /** @var GitBlameReader|null $git */
        $git = $context['git'] ?? null;
        $finder = new NodeFinder;
        $items = [];

        /** @var Class_[] $classes */
        $classes = $finder->findInstanceOf($ast, Class_::class);

        foreach ($classes as $class) {
            array_push($items, ...$this->analyzeClass($class, $filePath, $git, $finder));
        }

        return $items;
    }

    /**
     * @return DebtItem[]
     */
    private function analyzeClass(Class_ $class, string $filePath, ?GitBlameReader $git, NodeFinder $finder): array
    {
        $items = [];
        $skipMethods = array_merge(self::MAGIC_METHODS, self::LIFECYCLE_METHODS, $this->ignoreMethods);

        // Collect all internal method call sites within this class
        $calledMethods = [];

        foreach ($finder->findInstanceOf($class, Node\Expr\MethodCall::class) as $call) {
            if ($call->name instanceof Node\Identifier) {
                $calledMethods[] = $call->name->name;
            }
        }

        foreach ($finder->findInstanceOf($class, Node\Expr\StaticCall::class) as $call) {
            if ($call->name instanceof Node\Identifier) {
                $calledMethods[] = $call->name->name;
            }
        }

        // Collect all property fetch sites within this class
        $fetchedProperties = [];

        foreach ($finder->findInstanceOf($class, Node\Expr\PropertyFetch::class) as $fetch) {
            if ($fetch->name instanceof Node\Identifier) {
                $fetchedProperties[] = $fetch->name->name;
            }
        }

        foreach ($finder->findInstanceOf($class, Node\Expr\StaticPropertyFetch::class) as $fetch) {
            if ($fetch->name instanceof Node\Identifier) {
                $fetchedProperties[] = $fetch->name->name;
            }
        }

        // Collect all constant fetch sites within this class
        $fetchedConstants = [];

        foreach ($finder->findInstanceOf($class, Node\Expr\ClassConstFetch::class) as $fetch) {
            if ($fetch->name instanceof Node\Identifier) {
                $fetchedConstants[] = $fetch->name->name;
            }
        }

        // Promoted property names always count as used
        $promotedNames = $this->collectPromotedPropertyNames($class);

        // --- Check private methods ---
        foreach ($class->getMethods() as $method) {
            if (! $method->isPrivate()) {
                continue;
            }

            $name = $method->name->name;

            if (in_array($name, $skipMethods, true)) {
                continue;
            }

            if (! in_array($name, $calledMethods, true)) {
                $line = $method->getStartLine() ?: 0;
                $items[] = $this->makeItem($filePath, $line, $git,
                    "Unused private method: {$name}()",
                    self::BASE_SCORE_METHOD,
                );
            }
        }

        // --- Check private properties ---
        foreach ($class->getProperties() as $propertyGroup) {
            if (! $propertyGroup->isPrivate()) {
                continue;
            }

            foreach ($propertyGroup->props as $prop) {
                $name = $prop->name->name;

                if (in_array($name, $promotedNames, true)) {
                    continue;
                }

                if (! in_array($name, $fetchedProperties, true)) {
                    $line = $prop->getStartLine() ?: 0;
                    $items[] = $this->makeItem($filePath, $line, $git,
                        "Unused private property: \${$name}",
                        self::BASE_SCORE_PROPERTY,
                    );
                }
            }
        }

        // --- Check private constants ---
        foreach ($class->getConstants() as $constGroup) {
            if (! $constGroup->isPrivate()) {
                continue;
            }

            foreach ($constGroup->consts as $const) {
                $name = $const->name->name;

                if (! in_array($name, $fetchedConstants, true)) {
                    $line = $const->getStartLine() ?: 0;
                    $items[] = $this->makeItem($filePath, $line, $git,
                        "Unused private constant: {$name}",
                        self::BASE_SCORE_CONSTANT,
                    );
                }
            }
        }

        return $items;
    }

    /** @return string[] */
    private function collectPromotedPropertyNames(Class_ $class): array
    {
        $names = [];
        $constructor = $class->getMethod('__construct');

        if ($constructor === null) {
            return $names;
        }

        foreach ($constructor->params as $param) {
            if ($param->flags !== 0
                && $param->var instanceof Node\Expr\Variable
                && is_string($param->var->name)
            ) {
                $names[] = $param->var->name;
            }
        }

        return $names;
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
            type: 'dead_code',
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

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use TechRaysLabs\DebtTracker\Detectors\Contracts\DetectorInterface;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Detects likely Eloquent N+1 query patterns via static AST analysis.
 *
 * Flags property fetches and chained query calls on loop variables inside
 * foreach loops and collection iterators (each/map/filter).
 */
class N1QueryDetector implements DetectorInterface
{
    private const QUERY_CHAIN_METHODS = ['get', 'first', 'count', 'pluck', 'exists', 'toArray', 'paginate'];

    private const COLLECTION_ITER_METHODS = ['each', 'map', 'filter', 'flatMap'];

    private const BASE_SCORE_PROPERTY = 6;

    private const BASE_SCORE_QUERY_CHAIN = 10;

    /** @param string[] $ignoreProperties */
    public function __construct(
        private readonly bool $enabled = true,
        private readonly array $ignoreProperties = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'],
    ) {}

    public function getName(): string
    {
        return 'n1_queries';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param  array<string, mixed>  $context  Expects 'ast' (parsed nodes) and optionally 'git'
     * @return DebtItem[]
     */
    public function detect(string $filePath, array $context = []): array
    {
        if (! $this->enabled) {
            return [];
        }

        /** @var array<\PhpParser\Node\Stmt>|null $ast */
        $ast = $context['ast'] ?? null;

        if ($ast === null) {
            return [];
        }

        $source = @file_get_contents($filePath) ?: null;

        /** @var GitBlameReader|null $git */
        $git = $context['git'] ?? null;
        $finder = new NodeFinder;
        $items = [];

        $items = array_merge($items, $this->detectInForeachLoops($ast, $filePath, $source, $git, $finder));
        $items = array_merge($items, $this->detectInCollectionIterators($ast, $filePath, $source, $git, $finder));

        return $items;
    }

    /** @return DebtItem[] */
    private function detectInForeachLoops(
        array $ast,
        string $filePath,
        ?string $source,
        ?GitBlameReader $git,
        NodeFinder $finder,
    ): array {
        $items = [];

        /** @var Foreach_[] $foreachNodes */
        $foreachNodes = $finder->findInstanceOf($ast, Foreach_::class);

        foreach ($foreachNodes as $foreachNode) {
            if (! ($foreachNode->valueVar instanceof Variable)) {
                continue;
            }

            if (! is_string($foreachNode->valueVar->name)) {
                continue;
            }

            /** @var string $loopVar */
            $loopVar = $foreachNode->valueVar->name;

            if ($this->hasEagerLoad($foreachNode->stmts, $foreachNode->getStartLine(), $source)) {
                continue;
            }

            $items = array_merge(
                $items,
                $this->scanStmtsForN1($foreachNode->stmts, $loopVar, $filePath, $git, $finder),
            );
        }

        return $items;
    }

    /** @return DebtItem[] */
    private function detectInCollectionIterators(
        array $ast,
        string $filePath,
        ?string $source,
        ?GitBlameReader $git,
        NodeFinder $finder,
    ): array {
        $items = [];

        /** @var MethodCall[] $methodCalls */
        $methodCalls = $finder->findInstanceOf($ast, MethodCall::class);

        foreach ($methodCalls as $call) {
            $methodName = $call->name instanceof Identifier ? $call->name->name : null;

            if (! in_array($methodName, self::COLLECTION_ITER_METHODS, true)) {
                continue;
            }

            foreach ($call->args as $arg) {
                $closure = $arg->value;

                if (! ($closure instanceof Closure || $closure instanceof ArrowFunction)) {
                    continue;
                }

                if (empty($closure->params) || ! ($closure->params[0]->var instanceof Variable)) {
                    continue;
                }

                if (! is_string($closure->params[0]->var->name)) {
                    continue;
                }

                /** @var string $loopVar */
                $loopVar = $closure->params[0]->var->name;

                $closureStmts = $closure instanceof ArrowFunction
                    ? [$closure->expr]
                    : ($closure->stmts ?? []);

                if ($this->hasEagerLoad($closureStmts, $call->getStartLine(), $source)) {
                    continue;
                }

                $items = array_merge(
                    $items,
                    $this->scanStmtsForN1($closureStmts, $loopVar, $filePath, $git, $finder),
                );
            }
        }

        return $items;
    }

    /**
     * Scans statements for N+1 patterns on the given loop variable.
     *
     * @param  array<\PhpParser\Node>  $stmts
     * @return DebtItem[]
     */
    private function scanStmtsForN1(
        array $stmts,
        string $loopVar,
        string $filePath,
        ?GitBlameReader $git,
        NodeFinder $finder,
    ): array {
        $items = [];

        // Heuristic 1: property fetch on loop var (e.g. $item->user)
        /** @var PropertyFetch[] $propFetches */
        $propFetches = $finder->findInstanceOf($stmts, PropertyFetch::class);

        foreach ($propFetches as $fetch) {
            if (! ($fetch->var instanceof Variable) || $fetch->var->name !== $loopVar) {
                continue;
            }

            if (! ($fetch->name instanceof Identifier)) {
                continue;
            }

            $propName = $fetch->name->name;

            if (in_array($propName, $this->ignoreProperties, true)) {
                continue;
            }

            $line = $fetch->getStartLine();
            [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $line);

            $items[] = new DebtItem(
                type: 'n1_queries',
                filePath: $filePath,
                className: null,
                methodName: null,
                lineNumber: $line,
                description: "Possible N+1: property fetch '\${$loopVar}->{$propName}' inside loop",
                baseScore: self::BASE_SCORE_PROPERTY,
                ageMultiplier: $multiplier,
                ageBand: $ageBand,
                ageDays: $ageDays,
                gitAuthor: $author,
            );
        }

        // Heuristic 2: chained query call on loop var (e.g. $item->posts()->count())
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $finder->findInstanceOf($stmts, MethodCall::class);

        foreach ($methodCalls as $call) {
            $chainedMethod = $call->name instanceof Identifier ? $call->name->name : null;

            if (! in_array($chainedMethod, self::QUERY_CHAIN_METHODS, true)) {
                continue;
            }

            if (! ($call->var instanceof MethodCall)) {
                continue;
            }

            $inner = $call->var;

            if (! ($inner->var instanceof Variable) || $inner->var->name !== $loopVar) {
                continue;
            }

            $relationMethod = $inner->name instanceof Identifier ? $inner->name->name : '?';
            $line = $call->getStartLine();
            [$ageDays, $ageBand, $multiplier, $author] = $this->gitData($git, $filePath, $line);

            $items[] = new DebtItem(
                type: 'n1_queries',
                filePath: $filePath,
                className: null,
                methodName: null,
                lineNumber: $line,
                description: "Possible N+1: '\${$loopVar}->{$relationMethod}()->{$chainedMethod}()' inside loop",
                baseScore: self::BASE_SCORE_QUERY_CHAIN,
                ageMultiplier: $multiplier,
                ageBand: $ageBand,
                ageDays: $ageDays,
                gitAuthor: $author,
            );
        }

        return $items;
    }

    /**
     * Best-effort: checks if ->with( appears in the 30 lines preceding the loop.
     * Suppresses flagging when eager loading is detected nearby.
     * Comment lines (starting with * or //) are excluded from the check.
     *
     * @param  array<\PhpParser\Node>  $stmts
     */
    private function hasEagerLoad(array $stmts, int $loopStartLine, ?string $source): bool
    {
        if ($source === null) {
            return false;
        }

        $lines = explode("\n", $source);
        $start = max(0, $loopStartLine - 31);
        $end = $loopStartLine - 2;
        $slice = array_slice($lines, $start, $end - $start + 1);

        foreach ($slice as $line) {
            $trimmed = ltrim($line);
            // Skip comment lines
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            if (str_contains($line, '->with(') || str_contains($line, '::with(')) {
                return true;
            }
        }

        return false;
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

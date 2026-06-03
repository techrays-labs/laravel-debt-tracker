<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Analyzers;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

/**
 * Thin wrapper around nikic/php-parser with per-file AST caching.
 */
class AstParser
{
    /** @var array<string, array<Node\Stmt>|null> */
    private array $cache = [];

    private Parser $parser;

    private NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForVersion(PhpVersion::fromString('8.2'));
        $this->nodeFinder = new NodeFinder;
    }

    /**
     * Parses a file and returns its AST, using cache to avoid re-parsing.
     *
     * @return array<Node\Stmt>|null
     */
    public function parse(string $filePath): ?array
    {
        if (array_key_exists($filePath, $this->cache)) {
            return $this->cache[$filePath];
        }

        try {
            $source = @file_get_contents($filePath);

            if ($source === false) {
                trigger_error("DebtTracker: cannot read file {$filePath}", E_USER_WARNING);
                $this->cache[$filePath] = null;

                return null;
            }

            $ast = $this->parser->parse($source);
            $this->cache[$filePath] = $ast;

            return $ast;
        } catch (\Throwable) {
            trigger_error("DebtTracker: cannot parse {$filePath}", E_USER_WARNING);
            $this->cache[$filePath] = null;

            return null;
        }
    }

    /**
     * Returns all class-like (class, interface, trait, enum) nodes from an AST.
     *
     * @param  array<Node\Stmt>  $ast
     * @return ClassLike[]
     */
    public function getClasses(array $ast): array
    {
        return $this->nodeFinder->findInstanceOf($ast, ClassLike::class);
    }

    /**
     * Returns all method nodes belonging to a class-like node.
     *
     * @return ClassMethod[]
     */
    public function getMethods(ClassLike $class): array
    {
        return $this->nodeFinder->findInstanceOf([$class], ClassMethod::class);
    }

    /**
     * Counts statement nodes inside a method body (recursive).
     */
    public function countStatements(ClassMethod $method): int
    {
        if ($method->stmts === null) {
            return 0;
        }

        return count($this->nodeFinder->find($method->stmts, static function (Node $node): bool {
            return $node instanceof Node\Stmt;
        }));
    }

    /**
     * Measures cyclomatic complexity for a method.
     *
     * Starts at 1 and increments for each decision-making node.
     */
    public function measureCyclomaticComplexity(ClassMethod $method): int
    {
        if ($method->stmts === null) {
            return 1;
        }

        $complexity = 1;

        $nodes = $this->nodeFinder->find($method->stmts, static function (Node $node): bool {
            return $node instanceof Node\Stmt\If_
                || $node instanceof Node\Stmt\ElseIf_
                || $node instanceof Node\Stmt\For_
                || $node instanceof Node\Stmt\Foreach_
                || $node instanceof Node\Stmt\While_
                || $node instanceof Node\Stmt\Do_
                || $node instanceof Node\Stmt\Case_
                || $node instanceof Node\Stmt\Catch_
                || $node instanceof Node\Expr\BinaryOp\BooleanAnd
                || $node instanceof Node\Expr\BinaryOp\BooleanOr
                || $node instanceof Node\Expr\BinaryOp\LogicalAnd
                || $node instanceof Node\Expr\BinaryOp\LogicalOr
                || $node instanceof Node\Expr\Ternary
                || $node instanceof Node\Expr\NullsafeMethodCall
                || $node instanceof Node\Expr\BinaryOp\Coalesce;
        });

        $complexity += count($nodes);

        return $complexity;
    }

    /**
     * Measures the maximum nesting depth inside a method.
     */
    public function measureMaxNestingDepth(ClassMethod $method): int
    {
        if ($method->stmts === null) {
            return 0;
        }

        return $this->calcDepth($method->stmts, 0);
    }

    /**
     * @param  array<Node\Stmt>  $stmts
     */
    private function calcDepth(array $stmts, int $currentDepth): int
    {
        $max = $currentDepth;

        foreach ($stmts as $stmt) {
            $childStmts = $this->getBlockChildren($stmt);

            if (! empty($childStmts)) {
                $depth = $this->calcDepth($childStmts, $currentDepth + 1);
                $max = max($max, $depth);
            }
        }

        return $max;
    }

    /**
     * Returns nested statement arrays for block-creating nodes.
     *
     * @return array<Node\Stmt>
     */
    private function getBlockChildren(Node $node): array
    {
        $stmts = [];

        if ($node instanceof Node\Stmt\If_) {
            $stmts = array_merge($stmts, $node->stmts ?? []);
            foreach ($node->elseifs as $elseif) {
                $stmts = array_merge($stmts, $elseif->stmts ?? []);
            }
            if ($node->else !== null) {
                $stmts = array_merge($stmts, $node->else->stmts ?? []);
            }
        } elseif ($node instanceof Node\Stmt\For_
            || $node instanceof Node\Stmt\Foreach_
            || $node instanceof Node\Stmt\While_
            || $node instanceof Node\Stmt\Do_
        ) {
            $stmts = array_merge($stmts, $node->stmts ?? []);
        } elseif ($node instanceof Node\Stmt\TryCatch) {
            $stmts = array_merge($stmts, $node->stmts ?? []);
            foreach ($node->catches as $catch) {
                $stmts = array_merge($stmts, $catch->stmts ?? []);
            }
            if ($node->finally !== null) {
                $stmts = array_merge($stmts, $node->finally->stmts ?? []);
            }
        } elseif ($node instanceof Node\Stmt\Switch_) {
            foreach ($node->cases as $case) {
                $stmts = array_merge($stmts, $case->stmts ?? []);
            }
        }

        return $stmts;
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Agent;

use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Builds the one-sentence natural-language `summary` field required by the
 * agent-format contract (AGT-1), from a DebtItem's existing fields.
 */
final class AgentSummaryBuilder
{
    private const TYPE_LABELS = [
        'todo' => 'TODO/FIXME comment',
        'complexity' => 'complexity issue',
        'coverage' => 'test coverage gap',
        'dependency' => 'outdated dependency',
        'git_age' => 'aging code',
        'n1_queries' => 'N+1 query',
        'security' => 'security issue',
        'dead_code' => 'dead code',
    ];

    private const AGE_LABELS = [
        'fresh' => 'recently introduced',
        'growing' => 'growing',
        'chronic' => 'chronic',
        'critical' => 'critical',
    ];

    public function describe(DebtItem $item): string
    {
        $label = self::TYPE_LABELS[$item->type] ?? $item->type;
        $age = self::AGE_LABELS[$item->ageBand] ?? $item->ageBand;
        $location = $this->location($item);

        return "This {$label} in {$location} has been {$age} for {$item->ageDays} days and carries a score of {$item->finalScore()}.";
    }

    private function location(DebtItem $item): string
    {
        if ($item->className === null) {
            return basename($item->filePath);
        }

        $className = $this->shortClassName($item->className);

        return $item->methodName === null ? $className : "{$className}::{$item->methodName}()";
    }

    private function shortClassName(string $fqn): string
    {
        return substr($fqn, ((int) strrpos($fqn, '\\')) + 1);
    }
}

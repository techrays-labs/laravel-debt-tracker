<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Analyzers;

use TechRaysLabs\DebtTracker\ValueObjects\ClassDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Groups per-file debt items into per-class aggregates.
 */
class ClassAnalyzer
{
    /**
     * Groups all DebtItems by class name and returns ClassDebtResult[] sorted by totalScore desc.
     *
     * @param  DebtItem[]  $allItems
     * @return ClassDebtResult[]
     */
    public function extractClassResults(array $allItems): array
    {
        /** @var array<string, array{fqn: string, filePath: string, items: DebtItem[]}> $grouped */
        $grouped = [];

        foreach ($allItems as $item) {
            if ($item->className === null) {
                continue;
            }

            $key = $item->className;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'fqn' => $item->className,
                    'filePath' => $item->filePath,
                    'items' => [],
                ];
            }

            $grouped[$key]['items'][] = $item;
        }

        $results = [];

        foreach ($grouped as $className => $data) {
            $total = array_sum(array_map(static fn (DebtItem $i) => $i->finalScore(), $data['items']));

            $results[] = new ClassDebtResult(
                className: $className,
                fullyQualifiedName: $data['fqn'],
                filePath: $data['filePath'],
                items: $data['items'],
                totalScore: $total,
                itemCount: count($data['items']),
            );
        }

        usort($results, static fn (ClassDebtResult $a, ClassDebtResult $b) => $b->totalScore <=> $a->totalScore);

        return $results;
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Agent;

use Composer\InstalledVersions;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Builds the versioned `--format=agent` JSON contract (AGT-1..AGT-5).
 *
 * Deliberately independent of JsonReporter — the agent schema is versioned
 * and evolves on its own, without touching the existing --export=json shape.
 */
final class AgentFormatSerializer
{
    public const SCHEMA_VERSION = '1.0';

    public function __construct(private readonly AgentSummaryBuilder $summaries) {}

    /**
     * Builds the full agent-format payload for a scan result.
     *
     * @return array<string, mixed>
     */
    public function serialize(ScanResult $result, int $limit = 10): array
    {
        $allItems = array_merge([], ...array_map(
            static fn (FileDebtResult $f) => $f->items,
            $result->fileResults,
        ));

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'grade' => $result->grade,
            'total_score' => $result->totalScore,
            'estimated_hours' => round($result->estimatedHours, 1),
            'file_count' => count($result->fileResults),
            'item_count' => $result->totalItems(),
            'items' => array_map(fn (DebtItem $item) => $this->serializeItemForTool($item), $allItems),
            'priority' => array_map(fn (DebtItem $item) => $this->serializeItemForTool($item), $result->topItems($limit)),
            'meta' => [
                'package' => 'techrays-labs/laravel-debt-tracker',
                'version' => InstalledVersions::getPrettyVersion('techrays-labs/laravel-debt-tracker') ?? 'dev',
                'generated_at' => $result->generatedAt->format(\DateTimeInterface::ATOM),
            ],
        ];
    }

    /**
     * Builds the `{"error": {"code", "message"}}` payload (AGT-4).
     *
     * @return array{error: array{code: string, message: string}}
     */
    public function serializeError(string $code, string $message): array
    {
        return ['error' => ['code' => $code, 'message' => $message]];
    }

    /**
     * The per-item shape used inside "items"/"priority", also exposed
     * directly for the debt_show_file/debt_show_class MCP tools (MCP-3).
     *
     * @return array<string, mixed>
     */
    public function serializeItemForTool(DebtItem $item): array
    {
        return [
            'type' => $item->type,
            'file' => $item->filePath,
            'line_range' => ['start' => $item->lineNumber, 'end' => $item->lineNumber],
            'class_name' => $item->className,
            'method_name' => $item->methodName,
            'final_score' => $item->finalScore(),
            'age_band' => $item->ageBand,
            'age_days' => $item->ageDays,
            'summary' => $this->summaries->describe($item),
            'ai_tool' => $item->aiTool,
        ];
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Agent;

use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Gating\DebtGate;
use TechRaysLabs\DebtTracker\Gating\GateOptions;
use TechRaysLabs\DebtTracker\Support\DebtResultLookup;

/**
 * The four MCP tools exposed by debt:mcp-serve (MCP-2..MCP-4). Each method
 * reuses the exact same DebtTracker/AgentFormatSerializer/DebtGate/
 * DebtResultLookup calls as the CLI — no duplicated scoring or formatting.
 *
 * Read-only by design (MCP-5): no method accepts a shell command, a path
 * outside the configured project, or performs a file write.
 */
final class AgentTools
{
    public function __construct(
        private readonly DebtTracker $tracker,
        private readonly AgentFormatSerializer $serializer,
        private readonly DebtResultLookup $lookup,
        private readonly DebtGate $gate,
    ) {}

    /**
     * Runs a technical debt scan and returns the agent-format report.
     *
     * @param  string[]|null  $only  Detector names to run, e.g. ["todos", "complexity"]
     * @return array<string, mixed>
     */
    public function scan(?string $path = null, ?array $only = null, int $limit = 10): array
    {
        $result = $this->tracker->scan(
            paths: $path !== null ? [$path] : [],
            onlyDetectors: $only ?? [],
        );

        return $this->serializer->serialize($result, $limit);
    }

    /**
     * Shows the debt items for a single file, without a full scan.
     *
     * @return array{file: string, items: array<int, array<string, mixed>>}
     */
    public function showFile(string $path): array
    {
        $projectRoot = config('debt-tracker.project_root', base_path());
        $fileResult = $this->lookup->findFile($path, $projectRoot);

        return [
            'file' => $path,
            'items' => $fileResult === null
                ? []
                : array_map(fn ($item) => $this->serializer->serializeItemForTool($item), $fileResult->items),
        ];
    }

    /**
     * Shows the debt items for a single class by (fully-qualified) name.
     *
     * @return array{class: string, items: array<int, array<string, mixed>>}
     */
    public function showClass(string $fqcn): array
    {
        $classResult = $this->lookup->findClass($fqcn);

        return [
            'class' => $fqcn,
            'items' => $classResult === null
                ? []
                : array_map(fn ($item) => $this->serializer->serializeItemForTool($item), $classResult->items),
        ];
    }

    /**
     * Checks whether the project passes the configured CI debt gate.
     *
     * @return array{passed: bool, breached: string[], grade: string, total_score: int}
     */
    public function gateCheck(?string $failOnGrade = null, ?int $maxScore = null): array
    {
        $grade = GateOptions::normalizeGrade($failOnGrade ?? config('debt-tracker.ci.fail_on_grade'));
        $score = GateOptions::normalizeScore($maxScore ?? config('debt-tracker.ci.max_score'));

        $result = $this->tracker->scan();
        $gateResult = $this->gate->evaluate($result, $grade, $score);

        return [
            'passed' => $gateResult->passed,
            'breached' => array_values(array_filter([
                $gateResult->gradeBreached ? 'grade' : null,
                $gateResult->scoreBreached ? 'max_score' : null,
            ])),
            'grade' => $result->grade,
            'total_score' => $result->totalScore,
        ];
    }
}

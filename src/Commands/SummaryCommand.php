<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\Agent\AgentFormatSerializer;
use TechRaysLabs\DebtTracker\Agent\RendersAgentFormat;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Gating\DebtGate;
use TechRaysLabs\DebtTracker\Gating\ResolvesGateOptions;

/**
 * Single-line output for CI pipelines.
 *
 * Default exit codes (no gate flags / config): 0 = A or B, 1 = C, 2 = D or F.
 * When a gate threshold is configured (via --fail-on-grade / --max-score or the
 * debt-tracker.ci config block), the gate takes over: exit 0 when it passes,
 * exit 1 when a threshold is breached. Invalid flag input exits 2.
 *
 * --format=agent returns the identical schema as debt:scan --format=agent
 * (same serializer call) instead of the one-line text summary.
 */
class SummaryCommand extends Command
{
    use RendersAgentFormat;
    use ResolvesGateOptions;

    protected $signature = 'debt:summary
        {--format=full : Output format (full|agent)}
        {--limit=10 : Caps the "priority" array size in --format=agent}
        {--fail-on-grade= : Fail (exit 1) when the grade is this letter or worse (A-F)}
        {--max-score= : Fail (exit 1) when the total debt score exceeds this number}';

    protected $description = 'Get a one-line debt summary (CI-friendly)';

    public function handle(DebtTracker $tracker, DebtGate $gate, AgentFormatSerializer $serializer): int
    {
        $isAgentFormat = $this->isAgentFormat();

        try {
            [$failOnGrade, $maxScore] = $this->resolveGateThresholds();
        } catch (\InvalidArgumentException $e) {
            if ($isAgentFormat) {
                $this->renderAgentError($serializer, 'INVALID_OPTION', $e->getMessage());

                return self::INVALID;
            }

            $this->components->error($e->getMessage());

            return self::INVALID;
        }

        if ($isAgentFormat) {
            try {
                $result = $tracker->scan();
            } catch (\Throwable $e) {
                $this->renderAgentError($serializer, 'SCAN_FAILED', $e->getMessage());

                return 3;
            }

            $this->renderAgentPayload($serializer, $result, (int) $this->option('limit'));

            $gateResult = $gate->evaluate($result, $failOnGrade, $maxScore);

            return ($gateResult->active && ! $gateResult->passed) ? self::FAILURE : self::SUCCESS;
        }

        $result = $tracker->scan();

        $hours = number_format($result->estimatedHours, 1);
        $files = count($result->fileResults);

        $this->line(
            "[Techrays Debt Tracker] Grade: {$result->grade}"
            ." | Score: {$result->totalScore}"
            ." | Est: {$hours}h"
            ." | Files: {$files}"
        );

        $gateResult = $gate->evaluate($result, $failOnGrade, $maxScore);

        if ($gateResult->active) {
            if (! $gateResult->passed) {
                foreach ($gateResult->reasons as $reason) {
                    $this->components->error("Debt gate failed: {$reason}");
                }

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        // No gate configured — preserve the historical grade-based exit codes.
        return match ($result->grade) {
            'A', 'B' => 0,
            'C' => 1,
            default => 2,
        };
    }
}

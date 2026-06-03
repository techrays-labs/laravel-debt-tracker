<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\DebtTracker;

/**
 * Single-line output for CI pipelines. Exit codes: 0=A/B, 1=C, 2=D/F.
 */
class SummaryCommand extends Command
{
    protected $signature = 'debt:summary';

    protected $description = 'Get a one-line debt summary (CI-friendly)';

    public function handle(DebtTracker $tracker): int
    {
        $result = $tracker->scan();

        $hours = number_format($result->estimatedHours, 1);
        $files = count($result->fileResults);

        $this->line(
            "[Techrays Debt Tracker] Grade: {$result->grade}"
            ." | Score: {$result->totalScore}"
            ." | Est: {$hours}h"
            ." | Files: {$files}"
        );

        return match ($result->grade) {
            'A', 'B' => 0,
            'C' => 1,
            default => 2,
        };
    }
}

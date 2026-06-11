<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse;

use Laravel\Pulse\Facades\Pulse;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Writes a ScanResult to Laravel Pulse storage.
 *
 * Records a numeric time-series entry for the score trend and three
 * snapshot entries (summary, top files, top authors) for the remaining cards.
 * All writes are guarded by a container binding check so this class is safe to
 * instantiate even when laravel/pulse is not installed.
 */
class DebtPulseIngestor
{
    /**
     * Push a ScanResult into Pulse storage.
     * No-op when laravel/pulse is not installed.
     */
    public function push(ScanResult $result): void
    {
        if (! app()->bound(\Laravel\Pulse\Pulse::class)) {
            return;
        }

        // Time-series: one numeric entry per scan — powers the score trend chart.
        Pulse::record('debt_score', 'project', $result->totalScore)->max();

        // Snapshot: current grade, score, hours, item count, and category breakdown.
        Pulse::set('debt_summary', 'project', json_encode([
            'grade'      => $result->grade,
            'score'      => $result->totalScore,
            'hours'      => $result->estimatedHours,
            'items'      => $result->totalItems(),
            'byCategory' => $result->byCategory,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        // Snapshot: top 10 files by debt score.
        Pulse::set('debt_top_files', 'project', json_encode(
            array_map(
                static fn (FileDebtResult $f): array => [
                    'path'  => $f->relativePath,
                    'score' => $f->totalScore,
                ],
                $result->topFiles(10)
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));

        // Snapshot: top 10 authors by total debt score.
        Pulse::set('debt_top_authors', 'project', json_encode($result->topAuthors(10), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: current grade, score, estimated hours, and category breakdown.
 */
#[Lazy]
class DebtSummaryCard extends Card
{
    /**
     * Render the summary card with the latest debt scan data.
     */
    public function render(): \Illuminate\View\View
    {
        [$summary, $time, $runAt] = $this->remember(function (): mixed {
            $value = Pulse::values('debt_summary', ['project'])->first();

            return $value ? json_decode($value->value, true, 512, JSON_THROW_ON_ERROR) : null;
        });

        return view('debt-tracker-pulse::debt-summary-card', [
            'summary' => $summary,
            'time'    => $time,
            'runAt'   => $runAt,
        ]);
    }
}

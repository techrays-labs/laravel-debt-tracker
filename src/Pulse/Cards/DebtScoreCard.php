<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Closure;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: debt score trend over the past 14 days.
 */
#[Lazy]
class DebtScoreCard extends Card
{
    /**
     * Render the score trend card.
     */
    public function render(): View
    {
        [$scores, $time, $runAt] = $this->remember(
            Closure::fromCallable([$this, 'fetchDebtScoreGraph'])
        );

        return view('debt-tracker-pulse::debt-score-card', [
            'scores' => $scores,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }

    /** @internal called via Closure::fromCallable for Pulse remember() */
    public function fetchDebtScoreGraph(): mixed
    {
        return $this->graph(['debt_score'], 'max');
    }
}

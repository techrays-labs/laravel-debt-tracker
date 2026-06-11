<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

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
    public function render(): \Illuminate\View\View
    {
        [$scores, $time, $runAt] = $this->remember(
            fn (): mixed => $this->graph(['debt_score'], 'max')
        );

        return view('debt-tracker-pulse::debt-score-card', [
            'scores' => $scores,
            'time'   => $time,
            'runAt'  => $runAt,
        ]);
    }
}

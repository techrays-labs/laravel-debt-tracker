<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Carbon\CarbonInterval;
use Laravel\Pulse\Facades\Pulse;
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
        $scores = Pulse::graph(['debt_score'], 'max', CarbonInterval::days(14));

        return view('debt-tracker-pulse::debt-score-card', [
            'scores' => $scores,
        ]);
    }
}

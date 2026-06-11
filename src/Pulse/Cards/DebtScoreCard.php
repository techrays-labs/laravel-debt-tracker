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
    public function render(): \Illuminate\View\View
    {
        return view('debt-tracker-pulse::debt-score-card');
    }
}

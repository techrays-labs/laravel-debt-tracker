<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: current grade, score, estimated hours, and category breakdown.
 */
#[Lazy]
class DebtSummaryCard extends Card
{
    public function render(): \Illuminate\View\View
    {
        return view('debt-tracker-pulse::debt-summary-card');
    }
}

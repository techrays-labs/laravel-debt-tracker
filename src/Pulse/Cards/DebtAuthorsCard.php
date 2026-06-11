<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: top 10 authors by total debt score.
 */
#[Lazy]
class DebtAuthorsCard extends Card
{
    public function render(): \Illuminate\View\View
    {
        return view('debt-tracker-pulse::debt-authors-card');
    }
}

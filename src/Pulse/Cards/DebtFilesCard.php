<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: top 10 files with the highest debt score.
 */
#[Lazy]
class DebtFilesCard extends Card
{
    public function render(): \Illuminate\View\View
    {
        return view('debt-tracker-pulse::debt-files-card');
    }
}

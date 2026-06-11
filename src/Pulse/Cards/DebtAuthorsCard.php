<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: top 10 authors by total debt score.
 */
#[Lazy]
class DebtAuthorsCard extends Card
{
    /**
     * Render the debt authors leaderboard card.
     */
    public function render(): \Illuminate\View\View
    {
        $value = Pulse::values('debt_top_authors', ['project'])->first();
        $authors = $value ? json_decode($value->value, true) : [];

        return view('debt-tracker-pulse::debt-authors-card', [
            'authors' => $authors ?? [],
        ]);
    }
}

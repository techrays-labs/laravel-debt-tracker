<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Illuminate\View\View;
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
    public function render(): View
    {
        [$authors, $time, $runAt] = $this->remember(function (): mixed {
            $value = Pulse::values('debt_top_authors', ['project'])->first();

            return $value ? json_decode($value->value, true, 512, JSON_THROW_ON_ERROR) : [];
        });

        return view('debt-tracker-pulse::debt-authors-card', [
            'authors' => $authors ?? [],
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}

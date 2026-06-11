<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Pulse\Cards;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse card: top 10 files with the highest debt score.
 */
#[Lazy]
class DebtFilesCard extends Card
{
    /**
     * Render the hottest files card.
     */
    public function render(): \Illuminate\View\View
    {
        $value = Pulse::values('debt_top_files', ['project'])->first();
        $files = $value ? json_decode($value->value, true) : [];

        return view('debt-tracker-pulse::debt-files-card', [
            'files' => $files ?? [],
        ]);
    }
}

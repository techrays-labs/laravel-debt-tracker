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
        [$files, $time, $runAt] = $this->remember(function (): mixed {
            $value = Pulse::values('debt_top_files', ['project'])->first();

            return $value ? json_decode($value->value, true, 512, JSON_THROW_ON_ERROR) : [];
        });

        return view('debt-tracker-pulse::debt-files-card', [
            'files'  => $files ?? [],
            'time'   => $time,
            'runAt'  => $runAt,
        ]);
    }
}

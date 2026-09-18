<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\Support\DebtResultLookup;

/**
 * Shows a detailed debt breakdown for a class by its fully-qualified name.
 */
class ShowClassCommand extends Command
{
    protected $signature = 'debt:show-class {fqn : Fully qualified class name (e.g. App\\Services\\PaymentService)}';

    protected $description = 'Show debt breakdown for a specific class';

    public function handle(DebtResultLookup $lookup): int
    {
        $fqn = (string) $this->argument('fqn');

        $classResult = $lookup->findClass($fqn);

        if ($classResult === null) {
            $this->info("No debt found for class: {$fqn}");

            return self::SUCCESS;
        }

        $this->info("Debt breakdown for: {$fqn}");
        $this->line("Score: {$classResult->totalScore} | Items: {$classResult->itemCount}");
        $this->line('');

        $rows = array_map(static fn ($item) => [
            $item->lineNumber,
            $item->type,
            $item->methodName ?? '-',
            substr($item->description, 0, 60),
            $item->ageBand,
            $item->finalScore(),
        ], $classResult->items);

        $this->table(
            ['Line', 'Type', 'Method', 'Description', 'Age Band', 'Score'],
            $rows
        );

        return self::SUCCESS;
    }
}

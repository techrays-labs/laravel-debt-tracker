<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use TechRaysLabs\DebtTracker\Support\DebtResultLookup;

/**
 * Shows a detailed debt breakdown for a single file.
 */
class ShowFileCommand extends Command
{
    protected $signature = 'debt:show-file {path : Relative path to file}';

    protected $description = 'Show debt breakdown for a specific file';

    public function handle(DebtResultLookup $lookup): int
    {
        $relativePath = (string) $this->argument('path');
        $projectRoot = config('debt-tracker.project_root', base_path());

        try {
            $fileResult = $lookup->findFile($relativePath, $projectRoot);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($fileResult === null) {
            $this->info("No debt found in: {$relativePath}");

            return self::SUCCESS;
        }

        $this->info("Debt breakdown for: {$relativePath}");
        $this->line("Score: {$fileResult->totalScore} | Items: {$fileResult->itemCount}");
        $this->line('');

        $rows = array_map(static fn ($item) => [
            $item->lineNumber,
            $item->type,
            $item->methodName ?? '-',
            substr($item->description, 0, 60),
            $item->ageBand,
            $item->finalScore(),
        ], $fileResult->items);

        $this->table(
            ['Line', 'Type', 'Method', 'Description', 'Age Band', 'Score'],
            $rows
        );

        return self::SUCCESS;
    }
}

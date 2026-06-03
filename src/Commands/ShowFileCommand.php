<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\DebtTracker;

/**
 * Shows a detailed debt breakdown for a single file.
 */
class ShowFileCommand extends Command
{
    protected $signature = 'debt:show-file {path : Relative path to file}';

    protected $description = 'Show debt breakdown for a specific file';

    public function handle(DebtTracker $tracker): int
    {
        $relativePath = (string) $this->argument('path');
        $projectRoot = config('debt-tracker.project_root', base_path());
        $absolutePath = rtrim($projectRoot, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .ltrim($relativePath, DIRECTORY_SEPARATOR);

        if (! file_exists($absolutePath)) {
            $this->error("File not found: {$absolutePath}");

            return self::FAILURE;
        }

        $result = $tracker->scan(paths: [dirname($relativePath)]);

        foreach ($result->fileResults as $fileResult) {
            if ($fileResult->relativePath === $relativePath || $fileResult->filePath === $absolutePath) {
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

        $this->info("No debt found in: {$relativePath}");

        return self::SUCCESS;
    }
}

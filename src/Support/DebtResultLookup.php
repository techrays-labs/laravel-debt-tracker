<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Support;

use InvalidArgumentException;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\ValueObjects\ClassDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;

/**
 * Locates a single file's or class's debt result without requiring the
 * caller to run and search a full scan by hand.
 *
 * Shared by ShowFileCommand/ShowClassCommand and the debt_show_file/
 * debt_show_class MCP tools, so both call identical lookup logic.
 */
final class DebtResultLookup
{
    public function __construct(private readonly DebtTracker $tracker) {}

    /**
     * @throws InvalidArgumentException when the file does not exist on disk
     */
    public function findFile(string $relativePath, string $projectRoot): ?FileDebtResult
    {
        $absolutePath = rtrim($projectRoot, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .ltrim($relativePath, DIRECTORY_SEPARATOR);

        if (! file_exists($absolutePath)) {
            throw new InvalidArgumentException("File not found: {$absolutePath}");
        }

        $result = $this->tracker->scan(paths: [dirname($relativePath)]);

        foreach ($result->fileResults as $fileResult) {
            if ($fileResult->relativePath === $relativePath || $fileResult->filePath === $absolutePath) {
                return $fileResult;
            }
        }

        return null;
    }

    public function findClass(string $fqn): ?ClassDebtResult
    {
        $result = $this->tracker->scan();

        foreach ($result->classResults as $classResult) {
            if ($classResult->fullyQualifiedName === $fqn || $classResult->className === $fqn) {
                return $classResult;
            }
        }

        return null;
    }
}

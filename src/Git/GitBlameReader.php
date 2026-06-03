<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Git;

/**
 * Reads git blame and log data to determine debt item age and authorship.
 *
 * All shell commands use proc_open() with escapeshellarg() to prevent injection.
 */
class GitBlameReader
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly int $timeout = 30,
    ) {}

    /**
     * Returns true if git is available and the project root is inside a git repo.
     */
    public function isGitAvailable(): bool
    {
        [$exitCode] = $this->runCommand(['git', 'rev-parse', '--is-inside-work-tree']);

        return $exitCode === 0;
    }

    /**
     * Returns true if the given file is tracked by git.
     */
    public function isFileTracked(string $absolutePath): bool
    {
        $relative = $this->toRelative($absolutePath);
        [$exitCode] = $this->runCommand(['git', 'ls-files', '--error-unmatch', $relative]);

        return $exitCode === 0;
    }

    /**
     * Returns the age in days of the given line, or null if unavailable.
     */
    public function getLineAge(string $absolutePath, int $lineNumber): ?int
    {
        if (! $this->isGitAvailable() || ! $this->isFileTracked($absolutePath)) {
            return $this->fallbackAge($absolutePath);
        }

        $relative = $this->toRelative($absolutePath);
        [$exitCode, $stdout] = $this->runCommand([
            'git', 'blame', '-L', "{$lineNumber},{$lineNumber}", '--porcelain', $relative,
        ]);

        if ($exitCode !== 0 || empty($stdout)) {
            return $this->fallbackAge($absolutePath);
        }

        if (preg_match('/^author-time (\d+)$/m', $stdout, $matches)) {
            return (int) floor((time() - (int) $matches[1]) / 86400);
        }

        return null;
    }

    /**
     * Returns the age in days of the most recent commit touching this file.
     */
    public function getFileAge(string $absolutePath): ?int
    {
        if (! $this->isGitAvailable() || ! $this->isFileTracked($absolutePath)) {
            return $this->fallbackAge($absolutePath);
        }

        $relative = $this->toRelative($absolutePath);
        [$exitCode, $stdout] = $this->runCommand([
            'git', 'log', '-1', '--format=%ad', '--date=unix', '--', $relative,
        ]);

        if ($exitCode !== 0 || trim($stdout) === '') {
            return null;
        }

        return (int) floor((time() - (int) trim($stdout)) / 86400);
    }

    /**
     * Returns the git blame author for the given line, or null if unavailable.
     */
    public function getLineAuthor(string $absolutePath, int $lineNumber): ?string
    {
        if (! $this->isGitAvailable() || ! $this->isFileTracked($absolutePath)) {
            return null;
        }

        $relative = $this->toRelative($absolutePath);
        [$exitCode, $stdout] = $this->runCommand([
            'git', 'blame', '-L', "{$lineNumber},{$lineNumber}", '--porcelain', $relative,
        ]);

        if ($exitCode !== 0 || empty($stdout)) {
            return null;
        }

        if (preg_match('/^author (.+)$/m', $stdout, $matches)) {
            $author = trim($matches[1]);

            return $author === 'Not Committed Yet' ? null : $author;
        }

        return null;
    }

    /**
     * Maps an age in days to an age band string.
     */
    public function resolveAgeBand(int $ageDays): string
    {
        return match (true) {
            $ageDays < 30 => 'fresh',
            $ageDays < 90 => 'growing',
            $ageDays < 180 => 'chronic',
            default => 'critical',
        };
    }

    /**
     * Maps an age band to its score multiplier.
     */
    public function resolveAgeMultiplier(string $ageBand): float
    {
        return match ($ageBand) {
            'fresh' => 1.0,
            'growing' => 1.5,
            'chronic' => 2.0,
            'critical' => 3.0,
            default => 1.0,
        };
    }

    /**
     * Runs a git command from the project root and returns [exitCode, stdout].
     *
     * @param  string[]  $args
     * @return array{int, string}
     */
    protected function runCommand(array $args): array
    {
        $escaped = implode(' ', array_map('escapeshellarg', $args));
        $command = "cd {$this->escapedRoot()} && {$escaped}";

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptor, $pipes);

        if (! is_resource($process)) {
            return [-1, ''];
        }

        fclose($pipes[0]);

        stream_set_timeout($pipes[1], $this->timeout);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$exitCode, (string) $stdout];
    }

    /**
     * Converts an absolute path to a path relative to the project root.
     */
    private function toRelative(string $absolutePath): string
    {
        $root = rtrim($this->projectRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($absolutePath, $root)) {
            return substr($absolutePath, strlen($root));
        }

        return $absolutePath;
    }

    private function escapedRoot(): string
    {
        return escapeshellarg($this->projectRoot);
    }

    /**
     * Falls back to file mtime when git is unavailable.
     */
    private function fallbackAge(string $absolutePath): ?int
    {
        if (! file_exists($absolutePath)) {
            return null;
        }

        $mtime = filemtime($absolutePath);

        return $mtime !== false ? (int) floor((time() - $mtime) / 86400) : null;
    }
}

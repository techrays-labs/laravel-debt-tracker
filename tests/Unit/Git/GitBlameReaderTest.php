<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Git\GitBlameReader;

// --- resolveAgeBand boundary tests ---

it('resolves age band as fresh for 0 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(0))->toBe('fresh');
});

it('resolves age band as fresh for 29 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(29))->toBe('fresh');
});

it('resolves age band as growing for 30 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(30))->toBe('growing');
});

it('resolves age band as growing for 89 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(89))->toBe('growing');
});

it('resolves age band as chronic for 90 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(90))->toBe('chronic');
});

it('resolves age band as chronic for 179 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(179))->toBe('chronic');
});

it('resolves age band as critical for 180 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(180))->toBe('critical');
});

it('resolves age band as critical for 500 days', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeBand(500))->toBe('critical');
});

// --- resolveAgeMultiplier tests ---

it('returns 1.0 multiplier for fresh band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('fresh'))->toBe(1.0);
});

it('returns 1.5 multiplier for growing band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('growing'))->toBe(1.5);
});

it('returns 2.0 multiplier for chronic band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('chronic'))->toBe(2.0);
});

it('returns 3.0 multiplier for critical band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('critical'))->toBe(3.0);
});

it('returns 1.0 multiplier for unknown band', function () {
    $reader = new GitBlameReader('/tmp');
    expect($reader->resolveAgeMultiplier('unknown'))->toBe(1.0);
});

// --- Fallback when git is unavailable ---

it('getLineAge returns null gracefully for non-existent file when git unavailable', function () {
    $reader = new class('/tmp/non-existent-project') extends GitBlameReader
    {
        public function isGitAvailable(): bool
        {
            return false;
        }
    };

    expect($reader->getLineAge('/tmp/non-existent-project/SomeFile.php', 1))->toBeNull();
});

it('getLineAuthor returns null when git unavailable', function () {
    $reader = new class('/tmp/non-existent-project') extends GitBlameReader
    {
        public function isGitAvailable(): bool
        {
            return false;
        }
    };

    expect($reader->getLineAuthor('/tmp/non-existent-project/SomeFile.php', 1))->toBeNull();
});

// --- getLineAiTool ---

const FAKE_SHA = 'd34db33fd34db33fd34db33fd34db33fd34db33f';

/**
 * A GitBlameReader whose runCommand() is faked: any `blame` invocation
 * returns $blameOutput, any `log` invocation returns $trailerOutput, and
 * rev-parse/ls-files always "succeed" — so parsing/matching logic is
 * tested without touching a real git repository.
 */
function makeFakeAiReader(string $blameOutput, string $trailerOutput, ?array $aiCoAuthors = null): GitBlameReader
{
    return new class('/tmp/project', $blameOutput, $trailerOutput, $aiCoAuthors) extends GitBlameReader
    {
        public function __construct(
            string $root,
            private readonly string $blameOutput,
            private readonly string $trailerOutput,
            ?array $aiCoAuthors,
        ) {
            $aiCoAuthors === null ? parent::__construct($root) : parent::__construct($root, aiCoAuthors: $aiCoAuthors);
        }

        protected function runCommand(array $args): array
        {
            if (in_array('blame', $args, true)) {
                return [0, $this->blameOutput];
            }

            if (in_array('log', $args, true)) {
                return [0, $this->trailerOutput];
            }

            return [0, '']; // rev-parse / ls-files
        }
    };
}

function blamePorcelain(): string
{
    return FAKE_SHA." 1 1 1\nauthor Jane Doe\nauthor-mail <jane@example.com>\nauthor-time 1690000000\n\tsome code\n";
}

it('getLineAiTool returns null when git is unavailable', function () {
    $reader = new class('/tmp/non-existent-project') extends GitBlameReader
    {
        public function isGitAvailable(): bool
        {
            return false;
        }
    };

    expect($reader->getLineAiTool('/tmp/non-existent-project/SomeFile.php', 1))->toBeNull();
});

it('getLineAiTool returns null when the commit has no Co-authored-by trailer', function () {
    $reader = makeFakeAiReader(blamePorcelain(), '');

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBeNull();
});

it('getLineAiTool returns null when the co-author is human, not a known AI tool', function () {
    $reader = makeFakeAiReader(blamePorcelain(), "Jane Doe <jane@example.com>\n");

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBeNull();
});

it('getLineAiTool matches Claude via the anthropic.com email domain', function () {
    $reader = makeFakeAiReader(blamePorcelain(), "Claude <noreply@anthropic.com>\n");

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBe('Claude');
});

it('getLineAiTool matches GitHub Copilot', function () {
    $reader = makeFakeAiReader(blamePorcelain(), "copilot-swe-agent[bot] <198982749+Copilot@users.noreply.github.com>\n");

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBe('GitHub Copilot');
});

it('getLineAiTool matches the first AI co-author among several trailers', function () {
    $reader = makeFakeAiReader(blamePorcelain(), "Jane Doe <jane@example.com>\nAider <aider@aider.chat>\n");

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBe('Aider');
});

it('getLineAiTool respects a custom ai_co_authors map', function () {
    $reader = makeFakeAiReader(blamePorcelain(), "Internal Bot <bot@example.com>\n", ['Internal Bot' => ['internal bot']]);

    expect($reader->getLineAiTool('/tmp/project/File.php', 1))->toBe('Internal Bot');
});

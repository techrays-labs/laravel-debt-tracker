<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\DebtTracker;

/**
 * End-to-end coverage for the byAiTool aggregation (DebtTracker::scan()):
 * a real, disposable git repo with a genuine Co-authored-by trailer, so
 * the full getLineAiTool() -> DebtItem::$aiTool -> ScanResult::$byAiTool
 * pipeline is exercised against real `git log`/`git blame` output, not a
 * fake. Cleaned up unconditionally at the end.
 */
function makeAiAuthorshipFixtureRepo(): string
{
    $dir = sys_get_temp_dir().'/debt-tracker-ai-authorship-'.uniqid();
    mkdir($dir);

    $run = static function (string $command) use ($dir): void {
        exec('cd '.escapeshellarg($dir).' && '.$command.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("Command failed: {$command}\n".implode("\n", $output));
        }
    };

    $run('git init -q -b master');
    $run('git config user.email tester@example.com');
    $run('git config user.name "Test Author"');

    file_put_contents($dir.'/HumanClass.php', <<<'PHP'
        <?php

        class HumanClass
        {
            public function run(): void
            {
                // TODO: written by a human, no AI trailer
            }
        }
        PHP);
    $run('git add HumanClass.php');
    $run('git commit -q -m "add HumanClass"');

    file_put_contents($dir.'/AiAssistedClass.php', <<<'PHP'
        <?php

        class AiAssistedClass
        {
            public function run(): void
            {
                // TODO: written with Claude
            }
        }
        PHP);
    $run('git add AiAssistedClass.php');
    $run("git commit -q -m \"add AiAssistedClass\n\nCo-authored-by: Claude <noreply@anthropic.com>\"");

    return $dir;
}

function removeAiAuthorshipFixtureRepo(string $dir): void
{
    exec('rm -rf '.escapeshellarg($dir));
}

it('aggregates byAiTool from real Co-authored-by trailers across a scan', function () {
    $dir = makeAiAuthorshipFixtureRepo();

    try {
        $tracker = new DebtTracker([
            'project_root' => $dir,
            'scan_paths' => ['.'],
            'detectors' => ['coverage' => false, 'dependencies' => false],
        ]);

        $result = $tracker->scan();

        expect($result->byAiTool)->toHaveKey('Claude')
            ->and($result->byAiTool['Claude'])->toBeGreaterThan(0)
            ->and($result->byAuthor)->toHaveKey('Test Author')
            ->and($result->byAuthor['Test Author'])->toBeGreaterThan(0);
    } finally {
        removeAiAuthorshipFixtureRepo($dir);
    }
});

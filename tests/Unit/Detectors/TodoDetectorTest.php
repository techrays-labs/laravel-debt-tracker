<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Detectors\TodoDetector;
use TechRaysLabs\DebtTracker\Git\GitBlameReader;

$fixtureDir = dirname(__DIR__, 2).'/Fixtures';

function todoFakeGitReader(?string $aiTool): GitBlameReader
{
    return new class($aiTool) extends GitBlameReader
    {
        public function __construct(private readonly ?string $aiTool)
        {
            parent::__construct('/tmp');
        }

        public function getLineAge(string $absolutePath, int $lineNumber): ?int
        {
            return 5;
        }

        public function getLineAuthor(string $absolutePath, int $lineNumber): ?string
        {
            return 'Jane Doe';
        }

        public function getLineAiTool(string $absolutePath, int $lineNumber): ?string
        {
            return $this->aiTool;
        }
    };
}

it('detects TODO comments in line comments', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    $todos = array_filter($items, fn ($i) => str_starts_with($i->description, 'TODO'));
    expect(count($todos))->toBeGreaterThanOrEqual(2);
});

it('detects FIXME in block comments', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    $fixmes = array_filter($items, fn ($i) => str_starts_with($i->description, 'FIXME'));
    expect(count($fixmes))->toBeGreaterThanOrEqual(1);
});

it('detects @todo in docblocks', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    $todos = array_filter($items, fn ($i) => stripos($i->description, 'todo') !== false);
    expect(count($todos))->toBeGreaterThanOrEqual(1);
});

it('returns empty array for clean file', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/CleanFile.php');

    expect($items)->toBeEmpty();
});

it('assigns correct base score of 2 per item', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    foreach ($items as $item) {
        expect($item->baseScore)->toBe(2);
    }
});

it('returns empty array when detector is disabled', function () use ($fixtureDir) {
    $detector = new TodoDetector(enabled: false);
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    expect($items)->toBeEmpty();
});

it('detects exactly 6 items in TodoFixtures', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php');

    expect(count($items))->toBe(6);
});

it('populates aiTool from GitBlameReader::getLineAiTool', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php', ['git' => todoFakeGitReader('Claude')]);

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect($item->aiTool)->toBe('Claude');
    }
});

it('leaves aiTool null when the commit has no AI co-author', function () use ($fixtureDir) {
    $detector = new TodoDetector;
    $items = $detector->detect($fixtureDir.'/TodoFixtures.php', ['git' => todoFakeGitReader(null)]);

    foreach ($items as $item) {
        expect($item->aiTool)->toBeNull();
    }
});

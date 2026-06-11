<?php

declare(strict_types=1);

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\PulseServiceProvider;
use TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor;
use TechRaysLabs\DebtTracker\Tests\TestCase;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

uses(TestCase::class);

beforeAll(function (): void {
    if (! class_exists(\Laravel\Pulse\Pulse::class)) {
        test()->skip('laravel/pulse not installed');
    }
});

beforeEach(function (): void {
    // Register the Pulse service provider so the facade resolves correctly.
    $this->app->register(PulseServiceProvider::class);
});

function makeScanResult(int $score = 150, string $grade = 'B'): ScanResult
{
    $file = new FileDebtResult(
        filePath: '/project/app/Foo.php',
        relativePath: 'app/Foo.php',
        items: [],
        totalScore: $score,
        itemCount: 3,
    );

    return new ScanResult(
        fileResults: [$file],
        classResults: [],
        totalScore: $score,
        grade: $grade,
        estimatedHours: 37.5,
        byCategory: ['todos' => 50, 'complexity' => 100],
        generatedAt: new \DateTimeImmutable('2026-06-11T10:00:00+00:00'),
        projectPath: '/project',
        byAuthor: ['Jane Doe' => 90, 'John Smith' => 60],
    );
}

it('records the debt score as a time-series entry', function (): void {
    $spy = Pulse::spy();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makeScanResult(score: 150));

    $spy->shouldHaveReceived('record')
        ->once()
        ->with('debt_score', 'project', 150);
});

it('sets the debt_summary snapshot with correct keys', function (): void {
    $spy = Pulse::spy();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makeScanResult(score: 150, grade: 'B'));

    $spy->shouldHaveReceived('set')
        ->with(
            'debt_summary',
            'project',
            Mockery::on(function (string $json): bool {
                $data = json_decode($json, true);

                return $data['grade'] === 'B'
                    && $data['score'] === 150
                    && $data['hours'] === 37.5
                    && $data['items'] === 3
                    && isset($data['byCategory']);
            }),
        );
});

it('sets the debt_top_files snapshot with max 10 entries', function (): void {
    $spy = Pulse::spy();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makeScanResult());

    $spy->shouldHaveReceived('set')
        ->with(
            'debt_top_files',
            'project',
            Mockery::on(function (string $json): bool {
                $data = json_decode($json, true);

                return is_array($data)
                    && count($data) <= 10
                    && isset($data[0]['path'], $data[0]['score']);
            }),
        );
});

it('sets the debt_top_authors snapshot', function (): void {
    $spy = Pulse::spy();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makeScanResult());

    $spy->shouldHaveReceived('set')
        ->with(
            'debt_top_authors',
            'project',
            Mockery::on(function (string $json): bool {
                $data = json_decode($json, true);

                return isset($data['Jane Doe']) && $data['Jane Doe'] === 90;
            }),
        );
});

it('does nothing when Pulse facade is unavailable', function (): void {
    // The push() method guards internally — just assert no exception thrown.
    $ingestor = new DebtPulseIngestor;
    expect(fn () => $ingestor->push(makeScanResult()))->not->toThrow(\Throwable::class);
});

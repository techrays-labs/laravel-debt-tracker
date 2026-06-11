<?php

declare(strict_types=1);

use Laravel\Pulse\Entry;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\PulseServiceProvider;
use Mockery\MockInterface;
use TechRaysLabs\DebtTracker\Pulse\DebtPulseIngestor;
use TechRaysLabs\DebtTracker\Tests\TestCase;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

uses(TestCase::class);

beforeAll(function (): void {
    if (! class_exists(Laravel\Pulse\Pulse::class)) {
        test()->skip('laravel/pulse not installed');
    }
});

beforeEach(function (): void {
    // Register the Pulse service provider so the facade resolves correctly.
    $this->app->register(PulseServiceProvider::class);
});

/**
 * Create a spy on the Pulse facade that stubs record() to return a real Entry
 * instance so that the ->max() chain in DebtPulseIngestor resolves correctly.
 */
function pulseSpyWithEntryStub(): MockInterface
{
    $spy = Pulse::spy();

    // record() is typed to return Entry; return a real Entry so ->max() works.
    $spy->shouldReceive('record')
        ->andReturnUsing(fn (): Entry => new Entry(
            timestamp: (int) now()->timestamp,
            type: 'debt_score',
            key: 'project',
            value: 0,
        ));

    return $spy;
}

function makePulseScanResult(int $score = 150, string $grade = 'B'): ScanResult
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
        generatedAt: new DateTimeImmutable('2026-06-11T10:00:00+00:00'),
        projectPath: '/project',
        byAuthor: ['Jane Doe' => 90, 'John Smith' => 60],
    );
}

it('records the debt score as a time-series entry', function (): void {
    $spy = pulseSpyWithEntryStub();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makePulseScanResult(score: 150));

    $spy->shouldHaveReceived('record')
        ->once()
        ->with('debt_score', 'project', 150);
});

it('sets the debt_summary snapshot with correct keys', function (): void {
    $spy = pulseSpyWithEntryStub();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makePulseScanResult(score: 150, grade: 'B'));

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
    $spy = pulseSpyWithEntryStub();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makePulseScanResult());

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
    $spy = pulseSpyWithEntryStub();

    $ingestor = new DebtPulseIngestor;
    $ingestor->push(makePulseScanResult());

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

it('does nothing when Pulse is not bound in the container', function (): void {
    // Temporarily unbind the Pulse class to simulate a missing Pulse installation.
    // The guard checks app()->bound(\Laravel\Pulse\Pulse::class), so removing the
    // binding makes push() return early without any Pulse calls.
    $this->app->forgetInstance(Laravel\Pulse\Pulse::class);
    $this->app->offsetUnset(Laravel\Pulse\Pulse::class);

    $ingestor = new DebtPulseIngestor;
    expect(fn () => $ingestor->push(makePulseScanResult()))->not->toThrow(Throwable::class);

    // Re-register so other tests in the suite are unaffected.
    $this->app->register(PulseServiceProvider::class);
});

<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Agent\AgentFormatSerializer;
use TechRaysLabs\DebtTracker\Agent\AgentSummaryBuilder;
use TechRaysLabs\DebtTracker\Agent\AgentTools;
use TechRaysLabs\DebtTracker\DebtTracker;
use TechRaysLabs\DebtTracker\Gating\DebtGate;
use TechRaysLabs\DebtTracker\Scoring\GradeResolver;
use TechRaysLabs\DebtTracker\Support\DebtResultLookup;
use TechRaysLabs\DebtTracker\Tests\TestCase;

uses(TestCase::class);

function agentToolsFixturesRoot(): string
{
    return dirname(__DIR__, 3).'/tests/Fixtures';
}

function makeAgentTools(): AgentTools
{
    // AgentTools::showFile()/gateCheck() resolve project_root/ci defaults via
    // the config() facade (matching ShowFileCommand's existing convention),
    // so it must agree with the DebtTracker built below.
    config(['debt-tracker.project_root' => agentToolsFixturesRoot()]);

    // Scoped to the small "Lookup" fixture subdirectory — see
    // DebtResultLookupTest for why (deterministic item count, fast git-blame).
    $tracker = new DebtTracker([
        'project_root' => agentToolsFixturesRoot(),
        'scan_paths' => ['Lookup'],
        'detectors' => ['coverage' => false],
    ]);

    return new AgentTools(
        tracker: $tracker,
        serializer: new AgentFormatSerializer(new AgentSummaryBuilder),
        lookup: new DebtResultLookup($tracker),
        gate: new DebtGate(new GradeResolver),
    );
}

it('scan returns the full agent-format payload', function () {
    $payload = makeAgentTools()->scan();

    expect($payload)->toHaveKeys(['schema_version', 'grade', 'total_score', 'items', 'priority', 'meta'])
        ->and($payload['item_count'])->toBe(1);
});

it('scan respects the limit parameter', function () {
    $payload = makeAgentTools()->scan(limit: 0);

    expect($payload['priority'])->toBe([]);
});

it('showFile returns agent-format items for a known file', function () {
    $result = makeAgentTools()->showFile('Lookup/LookupFixture.php');

    expect($result['file'])->toBe('Lookup/LookupFixture.php')
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toHaveKeys(['type', 'file', 'line_range', 'summary']);
});

it('showFile returns an empty items array for a clean file', function () {
    $result = makeAgentTools()->showFile('Lookup/CleanLookupFixture.php');

    expect($result['items'])->toBe([]);
});

it('showClass returns agent-format items for a known class', function () {
    $result = makeAgentTools()->showClass('LookupFixture');

    expect($result['class'])->toBe('LookupFixture')
        ->and($result['items'])->toHaveCount(1);
});

it('showClass returns an empty items array for an unknown class', function () {
    $result = makeAgentTools()->showClass('App\Does\Not\Exist');

    expect($result['items'])->toBe([]);
});

it('gateCheck reports passed=true and empty breached when no threshold is breached', function () {
    $result = makeAgentTools()->gateCheck(maxScore: 1_000_000);

    expect($result['passed'])->toBeTrue()
        ->and($result['breached'])->toBe([]);
});

it('gateCheck reports the breached threshold name', function () {
    $result = makeAgentTools()->gateCheck(failOnGrade: 'A');

    expect($result['passed'])->toBeFalse()
        ->and($result['breached'])->toBe(['grade'])
        ->and($result)->toHaveKeys(['grade', 'total_score']);
});

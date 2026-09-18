<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Agent\AgentSummaryBuilder;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

function makeAgentItem(
    string $type = 'complexity',
    ?string $className = 'App\Services\PaymentService',
    ?string $methodName = 'process',
    string $ageBand = 'chronic',
    int $ageDays = 142,
    int $baseScore = 18,
): DebtItem {
    return new DebtItem(
        type: $type,
        filePath: '/app/Services/PaymentService.php',
        className: $className,
        methodName: $methodName,
        lineNumber: 88,
        description: 'test',
        baseScore: $baseScore,
        ageMultiplier: 1.0,
        ageBand: $ageBand,
        ageDays: $ageDays,
        gitAuthor: null,
    );
}

it('describes a complexity item with class and method context', function () {
    $summary = (new AgentSummaryBuilder)->describe(makeAgentItem());

    expect($summary)->toBe(
        'This complexity issue in PaymentService::process() has been chronic for 142 days and carries a score of 18.'
    );
});

it('falls back to the bare class name when no method is set', function () {
    $summary = (new AgentSummaryBuilder)->describe(makeAgentItem(
        type: 'coverage',
        methodName: null,
        ageBand: 'fresh',
        ageDays: 3,
        baseScore: 8,
    ));

    expect($summary)->toBe(
        'This test coverage gap in PaymentService has been recently introduced for 3 days and carries a score of 8.'
    );
});

it('falls back to the file basename when no class is set', function () {
    $summary = (new AgentSummaryBuilder)->describe(makeAgentItem(
        type: 'todo',
        className: null,
        methodName: null,
        ageBand: 'growing',
        ageDays: 45,
        baseScore: 2,
    ));

    expect($summary)->toBe(
        'This TODO/FIXME comment in PaymentService.php has been growing for 45 days and carries a score of 2.'
    );
});

it('falls back to the raw type label for an unknown detector type', function () {
    $summary = (new AgentSummaryBuilder)->describe(makeAgentItem(type: 'mystery_type', className: null, methodName: null));

    expect($summary)->toContain('This mystery_type in ');
});

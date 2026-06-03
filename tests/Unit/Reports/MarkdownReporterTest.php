<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Reports\MarkdownReporter;
use TechRaysLabs\DebtTracker\ValueObjects\ClassDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;
use TechRaysLabs\DebtTracker\ValueObjects\FileDebtResult;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

function makeScanResult(string $grade = 'C', int $score = 412): ScanResult
{
    $item = new DebtItem(
        type: 'todo',
        filePath: '/app/Services/PaymentService.php',
        className: 'PaymentService',
        methodName: 'process',
        lineNumber: 42,
        description: 'TODO: handle refunds',
        baseScore: 2,
        ageMultiplier: 1.0,
        ageBand: 'fresh',
        ageDays: 10,
        gitAuthor: 'dev',
    );

    $fileResult = new FileDebtResult(
        filePath: '/app/Services/PaymentService.php',
        relativePath: 'app/Services/PaymentService.php',
        items: [$item],
        totalScore: 2,
        itemCount: 1,
    );

    $classResult = new ClassDebtResult(
        className: 'PaymentService',
        fullyQualifiedName: 'App\\Services\\PaymentService',
        filePath: '/app/Services/PaymentService.php',
        items: [$item],
        totalScore: 2,
        itemCount: 1,
    );

    return new ScanResult(
        fileResults: [$fileResult],
        classResults: [$classResult],
        totalScore: $score,
        grade: $grade,
        estimatedHours: $score * 0.25,
        byCategory: ['todo' => $score],
        generatedAt: new DateTimeImmutable('2025-06-03 00:00:00'),
        projectPath: '/app',
    );
}

it('generates valid markdown with all sections', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult());

    expect($md)
        ->toContain('# Technical Debt Report')
        ->toContain('## Executive Summary')
        ->toContain('## Debt by Category')
        ->toContain('## Top 10 Worst Files')
        ->toContain('## Top 10 Worst Classes')
        ->toContain('## Full File Breakdown');
});

it('includes techrays labs credit in footer', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult());

    expect($md)->toContain('Techrays Labs');
    expect($md)->toContain('https://techrayslabs.com');
});

it('includes shields.io grade badge in header', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult('C'));

    expect($md)->toContain('img.shields.io/badge');
    expect($md)->toContain('-C-');
});

it('renders top files table correctly', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult());

    expect($md)->toContain('app/Services/PaymentService.php');
});

it('renders top classes table correctly', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult());

    expect($md)->toContain('App\\Services\\PaymentService');
});

it('uses details blocks for full breakdown', function () {
    $reporter = new MarkdownReporter;
    $md = $reporter->generate(makeScanResult());

    expect($md)->toContain('<details>');
    expect($md)->toContain('</details>');
});

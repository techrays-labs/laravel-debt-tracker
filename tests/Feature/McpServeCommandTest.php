<?php

declare(strict_types=1);

use Mcp\Client;
use Mcp\Client\Transport\StdioTransport;

/**
 * Integration tests for `debt:mcp-serve` (MCP-1..MCP-5, QA-2). Spawns the
 * real command as a subprocess over stdio and drives it with the MCP SDK's
 * own client — the only way to exercise the actual wire protocol.
 */
function connectMcpClient(): Client
{
    $client = Client::builder()->setClientInfo('debt-tracker-test-client', '1.0.0')->build();

    $client->connect(new StdioTransport(
        command: 'php',
        args: ['vendor/bin/testbench', 'debt:mcp-serve'],
        cwd: dirname(__DIR__, 2),
    ));

    return $client;
}

it('advertises exactly four tools, named debt_scan/debt_show_file/debt_show_class/debt_gate_check', function () {
    $client = connectMcpClient();

    $tools = $client->listTools()->tools;
    $names = array_map(static fn ($tool) => $tool->name, $tools);

    $client->disconnect();

    expect($names)->toHaveCount(4)
        ->and($names)->toEqualCanonicalizing([
            'debt_scan', 'debt_show_file', 'debt_show_class', 'debt_gate_check',
        ]);
});

it('calls debt_scan and gets back an agent-format payload', function () {
    $client = connectMcpClient();

    $result = $client->callTool('debt_scan', []);
    $client->disconnect();

    expect($result->isError)->toBeFalse()
        ->and($result->structuredContent)->toHaveKeys(['schema_version', 'grade', 'items', 'priority', 'meta']);
});

it('calls debt_gate_check and gets back passed/breached/grade/total_score', function () {
    $client = connectMcpClient();

    $result = $client->callTool('debt_gate_check', ['maxScore' => 1_000_000_000]);
    $client->disconnect();

    expect($result->isError)->toBeFalse()
        ->and($result->structuredContent)->toHaveKeys(['passed', 'breached', 'grade', 'total_score'])
        ->and($result->structuredContent['passed'])->toBeTrue();
});

it('calls debt_show_file for a nonexistent file without crashing the server', function () {
    $client = connectMcpClient();

    // DebtResultLookup::findFile() throws for a missing file; the SDK
    // surfaces an uncaught tool-handler exception as a RequestException
    // client-side rather than an isError result — either way, the server
    // process itself must not crash.
    $threw = false;

    try {
        $client->callTool('debt_show_file', ['path' => 'does/not/exist.php']);
    } catch (Throwable) {
        $threw = true;
    }

    // Server stays responsive after the failed lookup — a second call succeeds.
    $second = $client->callTool('debt_gate_check', ['maxScore' => 1_000_000_000]);
    $client->disconnect();

    expect($threw)->toBeTrue()
        ->and($second->isError)->toBeFalse();
});

it('rejects a tool call missing a required argument without crashing the server', function () {
    $client = connectMcpClient();

    $rejected = false;

    try {
        $client->callTool('debt_show_file', []);
    } catch (Throwable) {
        $rejected = true;
    }

    // Server stays responsive after the malformed call.
    $result = $client->callTool('debt_gate_check', ['maxScore' => 1_000_000_000]);
    $client->disconnect();

    expect($rejected)->toBeTrue()
        ->and($result->isError)->toBeFalse();
});

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use TechRaysLabs\DebtTracker\Agent\AgentTools;

/**
 * Starts a local, read-only MCP server exposing debt-tracker tools over
 * stdio (MCP-1). Registers exactly four tools explicitly — no directory
 * discovery — so the tool count can never silently grow (MCP-5).
 */
class McpServeCommand extends Command
{
    protected $signature = 'debt:mcp-serve';

    protected $description = 'Start a local, read-only MCP server exposing debt-tracker tools over stdio';

    public function handle(AgentTools $tools): int
    {
        if (! class_exists(Server::class)) {
            $this->components->error('The MCP SDK is not installed. Run: composer require mcp/sdk');

            return self::FAILURE;
        }

        $version = InstalledVersions::isInstalled('techrays-labs/laravel-debt-tracker')
            ? (InstalledVersions::getPrettyVersion('techrays-labs/laravel-debt-tracker') ?? 'dev')
            : 'dev';

        $server = Server::builder()
            ->setServerInfo('laravel-debt-tracker', $version)
            ->addTool([$tools, 'scan'], name: 'debt_scan', description: 'Run a technical debt scan and return the agent-format report.')
            ->addTool([$tools, 'showFile'], name: 'debt_show_file', description: 'Show the debt items for a single file, without a full scan.')
            ->addTool([$tools, 'showClass'], name: 'debt_show_class', description: 'Show the debt items for a single class by fully-qualified name.')
            ->addTool([$tools, 'gateCheck'], name: 'debt_gate_check', description: 'Check whether the project passes the configured CI debt gate.')
            ->build();

        $server->run(new StdioTransport);

        return self::SUCCESS;
    }
}

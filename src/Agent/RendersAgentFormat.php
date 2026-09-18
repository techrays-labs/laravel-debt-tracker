<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Agent;

use Illuminate\Console\Command;
use TechRaysLabs\DebtTracker\ValueObjects\ScanResult;

/**
 * Shared --format=agent handling for ScanCommand/SummaryCommand: detecting
 * the flag, and printing either the agent-format payload or the AGT-4 error
 * shape as the command's only stdout output.
 *
 * @mixin Command
 */
trait RendersAgentFormat
{
    protected function isAgentFormat(): bool
    {
        return $this->option('format') === 'agent';
    }

    protected function renderAgentPayload(AgentFormatSerializer $serializer, ScanResult $result, int $limit): void
    {
        $this->line(json_encode(
            $serializer->serialize($result, $limit),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    protected function renderAgentError(AgentFormatSerializer $serializer, string $code, string $message): void
    {
        $this->line(json_encode(
            $serializer->serializeError($code, $message),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}

<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Detectors\Contracts;

use TechRaysLabs\DebtTracker\ValueObjects\DebtItem;

/**
 * Contract that every debt detector must implement.
 */
interface DetectorInterface
{
    /**
     * Returns a unique machine-readable name for this detector.
     */
    public function getName(): string;

    /**
     * Runs detection on a single file and returns all found debt items.
     *
     * @param  array<string, mixed>  $context  Pre-built shared objects (e.g. 'ast', 'git')
     * @return DebtItem[]
     */
    public function detect(string $filePath, array $context = []): array;

    /**
     * Returns whether this detector is currently enabled.
     */
    public function isEnabled(): bool;
}

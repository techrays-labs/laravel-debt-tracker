<?php

// This fixture contains a known set of dead code items for deterministic testing.

class DeadCodeFixture
{
    private const UNUSED_CONST = 'never_used';       // unused private constant — score 3

    private string $unusedProperty = 'never_read';   // unused private property — score 5

    private string $usedProperty = 'will_be_read';   // USED — must NOT be flagged

    public function __construct(
        private readonly string $promotedProperty = 'promoted',  // promoted — must NOT be flagged
    ) {}

    private function unusedPrivateMethod(): string   // UNUSED — score 8
    {
        return 'never called';
    }

    private function usedPrivateMethod(): string     // USED — must NOT be flagged
    {
        return 'called below';
    }

    private function boot(): void                    // Laravel lifecycle — must NOT be flagged
    {
    }

    public function callUsedThings(): string
    {
        $x = $this->usedProperty;

        return $this->usedPrivateMethod();
    }
}
